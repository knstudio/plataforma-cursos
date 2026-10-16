<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Models\Order;
use App\Models\User;
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
}
