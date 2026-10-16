<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOfferTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->product = Product::factory()->create(['slug' => 'treinamento-essencial']);
    }

    public function test_admin_can_create_offer_with_brazilian_values(): void
    {
        $this->actingAs($this->admin)->get(route('admin.offers.create', $this->product))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.offers.store', $this->product), [
            'name' => 'Campanha WhatsApp Outubro',
            'price' => '9,90',
            'order_bump_price' => '7,00',
            'expires_at' => now()->addWeek()->toDateString(),
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $offer = $this->product->offers()->firstOrFail();
        $this->assertSame('campanha-whatsapp-outubro', $offer->code);
        $this->assertSame('9.90', $offer->price);
        $this->assertSame('7.00', $offer->order_bump_price);
        $this->assertSame('23:59:59', $offer->expires_at->format('H:i:s'));
        $this->assertTrue($offer->isAvailable());
    }

    public function test_code_must_be_unique_within_the_product(): void
    {
        ProductOffer::factory()->for($this->product)->create(['code' => 'whats']);

        $this->actingAs($this->admin)->post(route('admin.offers.store', $this->product), [
            'name' => 'Outra',
            'code' => 'whats',
            'price' => '10',
        ])->assertSessionHasErrors('code', errorBag: 'offer');
    }

    public function test_same_code_is_allowed_on_another_product(): void
    {
        ProductOffer::factory()->create(['code' => 'whats']);

        $this->actingAs($this->admin)->post(route('admin.offers.store', $this->product), [
            'name' => 'Whats',
            'code' => 'whats',
            'price' => '10',
        ])->assertSessionHasNoErrors();
    }

    public function test_admin_can_edit_offer(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create();

        $this->actingAs($this->admin)->get(route('admin.offers.edit', $offer))->assertOk();

        $this->actingAs($this->admin)->put(route('admin.offers.update', $offer), [
            'name' => 'Black Friday',
            'code' => 'black',
            'price' => '47,00',
            'without_order_bump' => '1',
        ])->assertSessionHasNoErrors();

        $offer->refresh();
        $this->assertSame('black', $offer->code);
        $this->assertTrue($offer->without_order_bump);
        $this->assertFalse($offer->is_active);
    }

    public function test_offer_with_sales_is_deactivated_instead_of_deleted(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create();
        Order::factory()->create(['product_id' => $this->product->id, 'product_offer_id' => $offer->id]);

        $this->actingAs($this->admin)->delete(route('admin.offers.destroy', $offer));

        $this->assertModelExists($offer);
        $this->assertFalse($offer->fresh()->is_active);
    }

    public function test_offer_without_sales_is_deleted(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create();

        $this->actingAs($this->admin)->delete(route('admin.offers.destroy', $offer));

        $this->assertModelMissing($offer);
    }

    public function test_product_page_lists_offers_with_sales(): void
    {
        $offer = ProductOffer::factory()->for($this->product)->create(['name' => 'Campanha Teste', 'code' => 'teste']);
        Order::factory()->create(['product_id' => $this->product->id, 'product_offer_id' => $offer->id, 'amount' => 9.9]);

        $this->actingAs($this->admin)->get(route('admin.products.edit', $this->product))
            ->assertOk()
            ->assertSee('Campanha Teste')
            ->assertSee('/checkout/treinamento-essencial/teste')
            ->assertSee('R$ 9,90');
    }
}
