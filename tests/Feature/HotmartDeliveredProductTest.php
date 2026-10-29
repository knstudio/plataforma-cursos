<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Products sold and delivered by Hotmart: only a record for the GoatFlow integration (contract, pendência 10).
 */
class HotmartDeliveredProductTest extends TestCase
{
    use RefreshDatabase;

    private const FLOW_URL = 'https://knflow.test/api/flows/hook/hotmart';

    public function test_admin_creates_it_with_only_name_slug_and_hotmart_id(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.products.store'), [
            'delivered_by_hotmart' => '1',
            'name' => 'Curso na Hotmart',
            'slug' => '',
            'hotmart_product_id' => ' 998877 ',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $product = Product::query()->where('slug', 'curso-na-hotmart')->firstOrFail();

        $this->assertTrue($product->delivered_by_hotmart);
        $this->assertSame('998877', $product->hotmart_product_id);
        $this->assertSame('0.00', $product->price);
        $this->assertCount(0, $product->courses);

        $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('Vendido e entregue pela Hotmart');
        $this->get(route('admin.products.index'))->assertOk()->assertSee('Vendido e entregue pela Hotmart');
    }

    public function test_hotmart_id_is_required(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.products.store'), [
            'delivered_by_hotmart' => '1',
            'name' => 'Curso na Hotmart',
        ])->assertSessionHasErrors('hotmart_product_id');
    }

    public function test_approval_records_the_order_and_notifies_goatflow_without_access_email(): void
    {
        Notification::fake();
        Http::fake();
        config(['services.hotmart.hottok' => 'hottok-secreto']);
        Product::factory()->create([
            'slug' => 'curso-na-hotmart', 'hotmart_product_id' => '998877', 'delivered_by_hotmart' => true,
            'price' => 0, 'purchase_webhook_url' => self::FLOW_URL,
        ]);

        $this->postJson(route('webhooks.hotmart'), [
            'event' => 'PURCHASE_APPROVED',
            'version' => '2.0.0',
            'data' => [
                'product' => ['id' => 998877],
                'buyer' => ['email' => 'compradora@example.com', 'name' => 'Compradora', 'checkout_phone' => '82999991234'],
                'purchase' => ['transaction' => 'HP1', 'price' => ['value' => 297]],
            ],
        ], ['X-HOTMART-HOTTOK' => 'hottok-secreto'])->assertOk();
        $this->app->terminate();

        $this->assertSame(OrderStatus::Approved, Order::query()->firstOrFail()->status);
        Notification::assertNothingSent();
        Http::assertSent(fn (Request $request) => $request->url() === self::FLOW_URL && $request['valor'] === '297,00');

        $this->getJson(route('api.funil.comprou', ['produto' => 'curso-na-hotmart', 'telefone' => '5582999991234']), ['Authorization' => 'Bearer '.Setting::integrationApiToken()])
            ->assertExactJson(['comprou' => true]);
    }

    public function test_it_has_no_checkout_and_shows_in_the_product_list(): void
    {
        $product = Product::factory()->create(['name' => 'Curso na Hotmart', 'slug' => 'curso-na-hotmart', 'delivered_by_hotmart' => true]);

        $this->get(route('checkout.show', $product))->assertNotFound();
        $this->getJson(route('api.funil.produtos'), ['Authorization' => 'Bearer '.Setting::integrationApiToken()])
            ->assertJsonFragment(['produto' => 'curso-na-hotmart', 'nome' => 'Curso na Hotmart']);
    }
}
