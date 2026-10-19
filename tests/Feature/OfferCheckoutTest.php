<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderBump;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OfferCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private OrderBump $orderBump;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.access_token' => 'TEST-token']);
        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'pref', 'init_point' => 'https://mp.test/pay'], 201)]);

        $bump = Product::factory()->create(['name' => 'Pac Esculturas']);
        $this->product = Product::factory()->create([
            'slug' => 'treinamento-essencial',
            'price' => 17,
        ]);
        $this->product->courses()->attach(Course::factory()->create());
        $this->orderBump = $this->product->orderBumps()->create(['bump_product_id' => $bump->id, 'price' => 12]);
    }

    public function test_offer_link_shows_the_special_price(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create(['code' => 'whats-out', 'price' => 9.90]);

        $this->get(route('checkout.offer.show', [$this->product, $offer]))
            ->assertOk()
            ->assertSee('/checkout/treinamento-essencial/whats-out')
            ->assertSee('R$ 9,90')
            ->assertSee('R$ 17,00'); // struck-through regular price
    }

    public function test_offer_price_and_bump_price_are_charged(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create(['price' => 9.90]);
        $offer->bumpOverrides()->create(['order_bump_id' => $this->orderBump->id, 'price' => 7]);

        $this->post(route('checkout.offer.store', [$this->product, $offer]), $this->buyer(withBump: true))
            ->assertRedirect('https://mp.test/pay');

        $order = Order::query()->firstOrFail();
        $this->assertSame($offer->id, $order->product_offer_id);
        $this->assertSame('16.90', $order->amount);

        Http::assertSent(fn (Request $request) => $request['items'][0]['unit_price'] === 9.9
            && $request['items'][1]['unit_price'] === 7.0);
    }

    public function test_offer_without_its_own_bump_price_uses_the_product_bump_price(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create(['price' => 9.90]);

        $this->post(route('checkout.offer.store', [$this->product, $offer]), $this->buyer(withBump: true));

        $this->assertSame('21.90', Order::query()->firstOrFail()->amount);
    }

    public function test_offer_can_hide_the_order_bump(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create(['price' => 9.90, 'without_order_bump' => true]);

        $this->get(route('checkout.offer.show', [$this->product, $offer]))->assertDontSee('Pac Esculturas');

        $this->post(route('checkout.offer.store', [$this->product, $offer]), $this->buyer(withBump: true));

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame('9.90', $order->amount);
        $this->assertCount(1, $order->items);
    }

    public function test_offer_can_remove_one_bump_and_reprice_another(): void
    {
        $apostila = Product::factory()->create(['name' => 'Apostila Extra']);
        $second = $this->product->orderBumps()->create(['bump_product_id' => $apostila->id, 'price' => 20, 'sort_order' => 1]);
        $offer = ProductOffer::factory()->for($this->product)->create(['price' => 9.90]);
        $offer->bumpOverrides()->create(['order_bump_id' => $this->orderBump->id, 'is_removed' => true]);
        $offer->bumpOverrides()->create(['order_bump_id' => $second->id, 'price' => 5]);

        $this->get(route('checkout.offer.show', [$this->product, $offer]))
            ->assertDontSee('Pac Esculturas')
            ->assertSee('Apostila Extra')
            ->assertSee('R$ 5,00');

        // The removed bump can't be bought through this link even if its id is posted.
        $this->post(route('checkout.offer.store', [$this->product, $offer]), [
            ...$this->buyer(),
            'order_bumps' => [$this->orderBump->id, $second->id],
        ]);

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame('14.90', $order->amount);
        $this->assertSame(['Apostila Extra'], $order->items->where('is_order_bump', true)->pluck('description')->values()->all());

        // The regular checkout still shows both bumps at their own prices.
        $this->get(route('checkout.show', $this->product))
            ->assertSee('Pac Esculturas')
            ->assertSee('R$ 20,00');
    }

    public function test_expired_offer_falls_back_to_the_regular_checkout(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->expired()->create();

        $this->get(route('checkout.offer.show', [$this->product, $offer]))
            ->assertRedirect(route('checkout.show', $this->product))
            ->assertSessionHas('error');

        $this->post(route('checkout.offer.store', [$this->product, $offer]), $this->buyer())
            ->assertRedirect(route('checkout.show', $this->product));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_inactive_offer_falls_back_to_the_regular_checkout(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create(['is_active' => false]);

        $this->get(route('checkout.offer.show', [$this->product, $offer]))
            ->assertRedirect(route('checkout.show', $this->product));
    }

    public function test_unknown_code_or_offer_from_another_product_returns_not_found(): void
    {
        $foreignOffer = ProductOffer::factory()->create(['code' => 'outro']);

        $this->get('/checkout/treinamento-essencial/nao-existe')->assertNotFound();
        $this->get('/checkout/treinamento-essencial/outro')->assertNotFound();
        $this->assertModelExists($foreignOffer);
    }

    /**
     * @return array<string, mixed>
     */
    private function buyer(bool $withBump = false): array
    {
        return array_filter([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'email_confirmation' => 'maria@example.com',
            'phone' => '11988887777',
            'order_bumps' => $withBump ? [$this->orderBump->id] : null,
        ]);
    }
}
