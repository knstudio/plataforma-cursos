<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\OrderBump;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AccessGranted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Product $bump;

    private OrderBump $orderBump;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.access_token' => 'TEST-token']);

        $this->bump = Product::factory()->create(['name' => 'Pac Esculturas', 'price' => 105]);
        $this->bump->courses()->attach(Course::factory()->create());

        $this->product = Product::factory()->create([
            'name' => 'Treinamento Essencial',
            'slug' => 'treinamento-essencial',
            'price' => 17,
        ]);
        $this->product->courses()->attach(Course::factory()->create());
        $this->orderBump = $this->product->orderBumps()->create(['bump_product_id' => $this->bump->id, 'price' => 12]);
    }

    public function test_checkout_page_shows_product_and_order_bump(): void
    {
        $this->get(route('checkout.show', $this->product))
            ->assertOk()
            ->assertSee('Treinamento Essencial')
            ->assertSee('R$ 17,00')
            ->assertSee('Pac Esculturas')
            ->assertSee('R$ 12,00');
    }

    public function test_checkout_shows_timer_banner_highlight_and_pixel(): void
    {
        Setting::store(['facebook_pixel_ids' => '111222333']);
        $this->product->update([
            'checkout_timer_minutes' => 20,
            'facebook_pixel_ids' => '444555666',
        ]);
        $this->orderBump->update(['headline' => 'Quero essa *OPORTUNIDADE ÚNICA*']);

        $this->get(route('checkout.show', $this->product))
            ->assertOk()
            ->assertSee('OFERTA POR TEMPO LIMITADO!')
            ->assertSee('data-minutes="20"', false)
            ->assertSee('assets/images/checkout/banner-padrao.jpg', false)
            ->assertSee('<span class="highlight">OPORTUNIDADE ÚNICA</span>', false)
            ->assertSee("fbq('init', '111222333')", false)
            ->assertSee("fbq('init', '444555666')", false)
            ->assertSee('InitiateCheckout');
    }

    public function test_no_pixel_code_without_configured_pixels(): void
    {
        $this->get(route('checkout.show', $this->product))
            ->assertOk()
            ->assertDontSee('fbevents.js', false);
    }

    public function test_timer_can_be_disabled(): void
    {
        $this->product->update(['checkout_timer_minutes' => 0]);

        $this->get(route('checkout.show', $this->product))
            ->assertOk()
            ->assertDontSee('OFERTA POR TEMPO LIMITADO!');
    }

    public function test_order_bump_headline_is_escaped(): void
    {
        $this->orderBump->update(['headline' => '<script>alert(1)</script> *oferta*']);

        $this->get(route('checkout.show', $this->product))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<span class="highlight">oferta</span>', false);
    }

    public function test_inactive_product_has_no_checkout(): void
    {
        $this->product->update(['is_active' => false]);

        $this->get(route('checkout.show', $this->product))->assertNotFound();
    }

    public function test_order_bump_is_hidden_when_bump_product_is_inactive(): void
    {
        $this->bump->update(['is_active' => false]);

        $this->get(route('checkout.show', $this->product))
            ->assertOk()
            ->assertDontSee('Pac Esculturas');
    }

    public function test_checkout_creates_order_with_database_prices_and_redirects_to_mercado_pago(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-123',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-123',
            ], 201),
        ]);

        $this->post(route('checkout.store', $this->product), [
            'name' => 'Maria Silva',
            'email' => 'Maria@Example.com',
            'email_confirmation' => 'Maria@Example.com',
            'phone' => '(11) 98888-7777',
            'order_bumps' => [$this->orderBump->id],
            // Tampering attempts are ignored: only buyer data is read.
            'price' => '1',
            'tipoCurso' => '1',
        ])->assertRedirect('https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-123');

        $order = Order::query()->with('items', 'user')->firstOrFail();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('29.00', $order->amount);
        $this->assertSame('pref-123', $order->preference_id);
        $this->assertCount(2, $order->items);
        $this->assertSame('maria@example.com', $order->user->email);
        $this->assertSame('11988887777', $order->user->phone);

        Http::assertSent(function (Request $request) use ($order) {
            return $request->url() === 'https://api.mercadopago.com/checkout/preferences'
                && $request->hasHeader('Authorization', 'Bearer TEST-token')
                && $request['external_reference'] === (string) $order->id
                && $request['items'][0]['unit_price'] === 17.0
                && $request['items'][1]['unit_price'] === 12.0
                && $request['payer']['email'] === 'maria@example.com';
        });
    }

    public function test_notifications_are_requested_only_in_webhooks_format(): void
    {
        config(['services.mercadopago.notification_url' => 'https://cursos.example.com/webhooks/mercadopago']);
        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'p', 'init_point' => 'https://mp.test/pay'], 201)]);

        $this->post(route('checkout.store', $this->product), [
            'name' => 'João',
            'email' => 'joao@example.com',
            'email_confirmation' => 'joao@example.com',
            'phone' => '11988887777',
        ]);

        Http::assertSent(fn (Request $request) => $request['notification_url'] === 'https://cursos.example.com/webhooks/mercadopago?source_news=webhooks');
    }

    public function test_several_order_bumps_can_be_bought_together(): void
    {
        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'p', 'init_point' => 'https://mp.test/pay'], 201)]);
        $apostila = Product::factory()->create(['name' => 'Apostila Extra', 'price' => 50]);
        $second = $this->product->orderBumps()->create(['bump_product_id' => $apostila->id, 'price' => 9.90, 'sort_order' => 1]);
        $notShown = OrderBump::factory()->create(['price' => 1]);

        $this->get(route('checkout.show', $this->product))
            ->assertSeeInOrder(['Pac Esculturas', 'Apostila Extra'])
            ->assertSee('name="order_bumps[]"', false);

        $this->post(route('checkout.store', $this->product), [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'email_confirmation' => 'maria@example.com',
            'phone' => '11988887777',
            // A bump from another product is ignored, whatever its price.
            'order_bumps' => [$this->orderBump->id, $second->id, $notShown->id],
        ])->assertRedirect('https://mp.test/pay');

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame('38.90', $order->amount);
        $this->assertSame(['Treinamento Essencial', 'Pac Esculturas', 'Apostila Extra'], $order->items->pluck('description')->all());
        $this->assertSame([false, true, true], $order->items->pluck('is_order_bump')->all());
    }

    public function test_checkout_without_bump_charges_only_main_product(): void
    {
        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'p', 'init_point' => 'https://mp.test/pay'], 201)]);

        $this->post(route('checkout.store', $this->product), [
            'name' => 'João',
            'email' => 'joao@example.com',
            'email_confirmation' => 'joao@example.com',
            'phone' => '11988887777',
        ])->assertRedirect('https://mp.test/pay');

        $this->assertSame('17.00', Order::query()->firstOrFail()->amount);
    }

    public function test_existing_student_is_reused(): void
    {
        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'p', 'init_point' => 'https://mp.test/pay'], 201)]);
        $student = User::factory()->create(['email' => 'aluna@example.com']);

        $this->post(route('checkout.store', $this->product), [
            'name' => 'Aluna',
            'email' => 'ALUNA@example.com',
            'email_confirmation' => 'ALUNA@example.com',
            'phone' => '11988887777',
        ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($student->id, Order::query()->firstOrFail()->user_id);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->post(route('checkout.store', $this->product), [
            'name' => 'João',
            'email' => 'joao@example.com',
            'email_confirmation' => 'joao@example.com',
            'phone' => '1234',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_email_must_be_typed_twice_identically(): void
    {
        $this->post(route('checkout.store', $this->product), [
            'name' => 'João',
            'email' => 'joao@example.com',
            'email_confirmation' => 'joao@exemple.com',
            'phone' => '11988887777',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_email_confirmation_ignores_case_and_spaces(): void
    {
        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'p', 'init_point' => 'https://mp.test/pay'], 201)]);

        $this->post(route('checkout.store', $this->product), [
            'name' => 'João',
            'email' => 'Joao@Example.com',
            'email_confirmation' => ' joao@example.com ',
            'phone' => '11988887777',
        ])->assertRedirect('https://mp.test/pay');
    }

    public function test_mercado_pago_failure_returns_to_checkout(): void
    {
        Http::fake(['api.mercadopago.com/*' => Http::response(['message' => 'invalid token'], 401)]);

        $this->from(route('checkout.show', $this->product))
            ->post(route('checkout.store', $this->product), [
                'name' => 'João',
                'email' => 'joao@example.com',
                'email_confirmation' => 'joao@example.com',
                'phone' => '11988887777',
            ])
            ->assertRedirect(route('checkout.show', $this->product))
            ->assertSessionHas('error');

        $this->assertSame(OrderStatus::Cancelled, Order::query()->firstOrFail()->status);
    }

    public function test_result_page_requires_a_signed_link(): void
    {
        $order = Order::factory()->pending()->create();

        $this->get(route('checkout.result', $order))->assertForbidden();
    }

    public function test_result_page_syncs_the_payment_from_the_return_url(): void
    {
        Notification::fake();
        $order = Order::factory()->pending()->create(['product_id' => $this->product->id, 'amount' => 17]);
        $order->items()->create(['product_id' => $this->product->id, 'description' => 'Treinamento', 'unit_price' => 17]);

        Http::fake([
            'api.mercadopago.com/v1/payments/987' => Http::response([
                'id' => 987,
                'status' => 'approved',
                'external_reference' => (string) $order->id,
                'transaction_amount' => 17,
                'payment_method_id' => 'pix',
                'date_approved' => now()->toIso8601String(),
            ]),
        ]);

        $url = URL::signedRoute('checkout.result', ['order' => $order]).'&payment_id=987&status=approved&collection_status=approved';

        $this->get($url)
            ->assertOk()
            ->assertSee('Pagamento aprovado');

        $order->refresh();
        $this->assertSame(OrderStatus::Approved, $order->status);
        $this->assertSame('987', $order->external_id);
        $this->assertSame('pix', $order->payment_method);
        $this->assertTrue($order->user->canAccessCourse($this->product->courses->first()));
        Notification::assertSentTo($order->user, AccessGranted::class);
    }
}
