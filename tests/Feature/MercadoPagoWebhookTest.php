<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use App\Models\WebhookCall;
use App\Notifications\AccessGranted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    private Course $mainCourse;

    private Course $bumpCourse;

    /**
     * Payment returned by the faked Mercado Pago API; tests change it between notifications.
     *
     * @var array<string, mixed>
     */
    private array $payment = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.access_token' => 'TEST-token', 'services.mercadopago.webhook_secret' => null]);
        Notification::fake();
        Http::fake(['api.mercadopago.com/v1/payments/*' => fn () => Http::response($this->payment)]);

        $main = Product::factory()->create(['price' => 17]);
        $bump = Product::factory()->create(['price' => 105]);
        $this->mainCourse = Course::factory()->create();
        $this->bumpCourse = Course::factory()->create();
        $main->courses()->attach($this->mainCourse);
        $bump->courses()->attach($this->bumpCourse);

        $this->order = Order::factory()->pending()->create(['product_id' => $main->id, 'amount' => 29, 'external_id' => null]);
        $this->order->items()->createMany([
            ['product_id' => $main->id, 'description' => 'Principal', 'unit_price' => 17],
            ['product_id' => $bump->id, 'description' => 'Bump', 'unit_price' => 12, 'is_order_bump' => true],
        ]);
    }

    public function test_approved_payment_grants_all_purchased_courses_once(): void
    {
        $this->fakePayment('approved');

        $this->notify()->assertOk();
        $this->notify()->assertOk();

        $this->order->refresh();
        $this->assertSame(OrderStatus::Approved, $this->order->status);
        $this->assertTrue($this->order->user->canAccessCourse($this->mainCourse));
        $this->assertTrue($this->order->user->canAccessCourse($this->bumpCourse));
        Notification::assertSentToTimes($this->order->user, AccessGranted::class, 1);
        $this->assertSame(2, WebhookCall::query()->where('result', WebhookCall::RESULT_PROCESSED)->count());
    }

    public function test_pending_payment_keeps_order_pending(): void
    {
        $this->fakePayment('pending');

        $this->notify()->assertOk();

        $this->assertSame(OrderStatus::Pending, $this->order->fresh()->status);
        $this->assertFalse($this->order->user->canAccessCourse($this->mainCourse));
    }

    public function test_refund_revokes_access(): void
    {
        $this->fakePayment('approved');
        $this->notify();

        $this->fakePayment('refunded');
        $this->notify()->assertOk();

        $this->assertSame(OrderStatus::Refunded, $this->order->fresh()->status);
        $this->assertFalse($this->order->user->canAccessCourse($this->mainCourse));
        $this->assertFalse($this->order->user->canAccessCourse($this->bumpCourse));
    }

    public function test_chargeback_revokes_access(): void
    {
        $this->fakePayment('approved');
        $this->notify();

        $this->fakePayment('charged_back');
        $this->notify();

        $this->assertSame(OrderStatus::Chargeback, $this->order->fresh()->status);
        $this->assertFalse($this->order->user->canAccessCourse($this->mainCourse));
    }

    public function test_payment_with_lower_amount_is_not_approved(): void
    {
        $this->fakePayment('approved', amount: 1);

        $this->notify()->assertServerError();

        $this->assertSame(OrderStatus::Pending, $this->order->fresh()->status);
        $this->assertSame(WebhookCall::RESULT_FAILED, WebhookCall::query()->firstOrFail()->result);
    }

    public function test_rejected_payment_cancels_but_a_later_approval_still_works(): void
    {
        $this->fakePayment('rejected', paymentId: 111);
        $this->notify(111);
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);

        $this->fakePayment('approved', paymentId: 222);
        $this->notify(222);
        $this->assertSame(OrderStatus::Approved, $this->order->fresh()->status);
        $this->assertSame('222', $this->order->fresh()->external_id);
    }

    public function test_payment_from_another_system_is_ignored(): void
    {
        $this->payment = ['id' => 555, 'status' => 'approved', 'external_reference' => 'sis-antigo-99', 'transaction_amount' => 17];

        $this->notify(555)->assertOk();

        $this->assertSame(WebhookCall::RESULT_IGNORED, WebhookCall::query()->firstOrFail()->result);
    }

    public function test_non_payment_notifications_are_ignored(): void
    {
        $this->postJson(route('webhooks.mercadopago'), ['type' => 'merchant_order', 'data' => ['id' => '1']])->assertOk();

        Http::assertNothingSent();
    }

    public function test_legacy_ipn_format_is_supported(): void
    {
        $this->fakePayment('approved');

        $this->post(route('webhooks.mercadopago', ['topic' => 'payment', 'id' => 123456]))->assertOk();

        $this->assertSame(OrderStatus::Approved, $this->order->fresh()->status);
    }

    public function test_signature_is_checked_when_secret_is_configured(): void
    {
        config(['services.mercadopago.webhook_secret' => 'segredo']);
        $this->fakePayment('approved');

        $this->notify()->assertUnauthorized();
        $this->assertSame(OrderStatus::Pending, $this->order->fresh()->status);

        $timestamp = '1742505638683';
        $hash = hash_hmac('sha256', "id:123456;request-id:req-1;ts:{$timestamp};", 'segredo');

        $this->postJson(
            route('webhooks.mercadopago', ['data.id' => 123456, 'type' => 'payment']),
            ['type' => 'payment', 'action' => 'payment.updated', 'data' => ['id' => '123456']],
            ['x-signature' => "ts={$timestamp},v1={$hash}", 'x-request-id' => 'req-1'],
        )->assertOk();

        $this->assertSame(OrderStatus::Approved, $this->order->fresh()->status);
    }

    private function fakePayment(string $status, float $amount = 29, int $paymentId = 123456): void
    {
        $this->payment = [
            'id' => $paymentId,
            'status' => $status,
            'external_reference' => (string) $this->order->id,
            'transaction_amount' => $amount,
            'payment_method_id' => 'pix',
            'date_approved' => $status === 'approved' ? now()->toIso8601String() : null,
        ];
    }

    private function notify(int $paymentId = 123456): TestResponse
    {
        return $this->postJson(
            route('webhooks.mercadopago', ['data.id' => $paymentId, 'type' => 'payment']),
            ['type' => 'payment', 'action' => 'payment.updated', 'data' => ['id' => (string) $paymentId]],
        );
    }
}
