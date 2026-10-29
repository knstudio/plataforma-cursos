<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_can_be_filtered(): void
    {
        $admin = User::factory()->admin()->create();
        $approved = Order::factory()->for(User::factory()->state(['email' => 'aprovado@example.com']))->create();
        Order::factory()->pending()->for(User::factory()->state(['email' => 'pendente@example.com']))->create();

        $this->actingAs($admin)->get(route('admin.orders.index', ['status' => OrderStatus::Approved->value]))
            ->assertOk()
            ->assertSee('aprovado@example.com')
            ->assertDontSee('pendente@example.com');

        $this->actingAs($admin)->get(route('admin.orders.index', ['q' => $approved->external_id]))
            ->assertOk()
            ->assertSee('aprovado@example.com')
            ->assertDontSee('pendente@example.com');

        $this->actingAs($admin)->get(route('admin.orders.index', ['gateway' => PaymentGateway::Hotmart->value]))
            ->assertOk()
            ->assertDontSee('aprovado@example.com');
    }

    public function test_admin_can_sync_order_with_mercado_pago(): void
    {
        Notification::fake();
        config(['services.mercadopago.access_token' => 'TEST-token']);
        $order = Order::factory()->pending()->create(['external_id' => '777', 'amount' => 17]);

        Http::fake(['api.mercadopago.com/v1/payments/777' => Http::response([
            'id' => 777,
            'status' => 'approved',
            'external_reference' => (string) $order->id,
            'transaction_amount' => 17,
        ])]);

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.sync', $order))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);
    }

    public function test_order_detail_shows_gateway_payload(): void
    {
        $order = Order::factory()->create(['payload' => ['transaction' => 'HP123456']]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('HP123456');
    }

    public function test_admin_marks_an_approved_order_as_refunded_and_access_goes_away(): void
    {
        $course = Course::factory()->create();
        $product = Product::factory()->create();
        $product->courses()->attach($course);
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->pending()->create(['product_id' => $product->id]);
        app(OrderFulfillment::class)->approve($order);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Marcar como Reembolsado')
            ->assertDontSee('Marcar como Aprovado');

        $this->actingAs($admin)->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Refunded->value])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertFalse($user->fresh()->canAccessCourse($course));
    }

    public function test_admin_approves_a_pending_order_by_hand(): void
    {
        Notification::fake();
        $course = Course::factory()->create();
        $product = Product::factory()->create();
        $product->courses()->attach($course);
        $order = Order::factory()->pending()->create(['product_id' => $product->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Approved->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);
        $this->assertTrue($order->user->fresh()->canAccessCourse($course));
    }

    public function test_status_not_offered_for_the_order_is_refused(): void
    {
        $order = Order::factory()->pending()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Refunded->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }
}
