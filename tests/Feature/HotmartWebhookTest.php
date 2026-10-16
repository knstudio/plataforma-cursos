<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebhookCall;
use App\Notifications\AccessGranted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HotmartWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.hotmart.hottok' => 'hottok-secreto']);
        Notification::fake();

        $product = Product::factory()->create(['hotmart_product_id' => '4565']);
        $this->course = Course::factory()->create();
        $product->courses()->attach($this->course);
    }

    public function test_approved_purchase_creates_student_order_and_access(): void
    {
        $this->send('PURCHASE_APPROVED')->assertOk();

        $student = User::query()->where('email', 'compradora@example.com')->firstOrFail();
        $order = Order::query()->firstOrFail();

        $this->assertSame('Compradora Teste', $student->name);
        $this->assertSame(PaymentGateway::Hotmart, $order->gateway);
        $this->assertSame('HP1121334455', $order->external_id);
        $this->assertSame(OrderStatus::Approved, $order->status);
        $this->assertSame('17.00', $order->amount);
        $this->assertTrue($student->canAccessCourse($this->course));
        Notification::assertSentTo($student, AccessGranted::class);
    }

    public function test_complete_after_approved_does_not_duplicate(): void
    {
        $this->send('PURCHASE_APPROVED');
        $this->send('PURCHASE_COMPLETE')->assertOk();

        $this->assertDatabaseCount('orders', 1);
        Notification::assertSentTimes(AccessGranted::class, 1);
    }

    public function test_refund_revokes_access(): void
    {
        $this->send('PURCHASE_APPROVED');
        $this->send('PURCHASE_REFUNDED')->assertOk();

        $student = User::query()->where('email', 'compradora@example.com')->firstOrFail();
        $this->assertSame(OrderStatus::Refunded, Order::query()->firstOrFail()->status);
        $this->assertFalse($student->canAccessCourse($this->course));
    }

    public function test_wrong_hottok_is_refused(): void
    {
        $this->send('PURCHASE_APPROVED', hottok: 'chute')->assertUnauthorized();

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(WebhookCall::RESULT_FAILED, WebhookCall::query()->firstOrFail()->result);
    }

    public function test_everything_is_refused_when_hottok_is_not_configured(): void
    {
        config(['services.hotmart.hottok' => null]);

        $this->send('PURCHASE_APPROVED', hottok: '')->assertUnauthorized();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_hottok_in_body_is_accepted(): void
    {
        $this->postJson(route('webhooks.hotmart'), [...$this->payload('PURCHASE_APPROVED'), 'hottok' => 'hottok-secreto'])
            ->assertOk();

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_unknown_product_is_ignored(): void
    {
        $this->send('PURCHASE_APPROVED', productId: 9999)->assertOk();

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(WebhookCall::RESULT_IGNORED, WebhookCall::query()->firstOrFail()->result);
    }

    public function test_unused_events_are_ignored(): void
    {
        $this->send('PURCHASE_BILLET_PRINTED')->assertOk();

        $this->assertDatabaseCount('orders', 0);
    }

    private function send(string $event, string $hottok = 'hottok-secreto', int $productId = 4565): TestResponse
    {
        return $this->postJson(route('webhooks.hotmart'), $this->payload($event, $productId), ['X-HOTMART-HOTTOK' => $hottok]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $event, int $productId = 4565): array
    {
        return [
            'id' => 'evt-'.$event,
            'creation_date' => 1760000000000,
            'event' => $event,
            'version' => '2.0.0',
            'data' => [
                'product' => ['id' => $productId, 'name' => 'Treinamento Essencial'],
                'buyer' => ['email' => 'Compradora@Example.com', 'name' => 'Compradora Teste', 'checkout_phone' => '11999998888'],
                'purchase' => [
                    'transaction' => 'HP1121334455',
                    'status' => 'APPROVED',
                    'approved_date' => 1760000000000,
                    'price' => ['value' => 17, 'currency_value' => 'BRL'],
                    'payment' => ['type' => 'PIX'],
                ],
            ],
        ];
    }
}
