<?php

namespace Tests\Feature;

use App\Enums\FunilInscricaoStatus;
use App\Models\Funil;
use App\Models\FunilBloco;
use App\Models\FunilContato;
use App\Models\FunilGatilho;
use App\Models\FunilInscricao;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FunilComprasTest extends TestCase
{
    use RefreshDatabase;

    private Product $essencial;

    private FunilBloco $blocoEssencial;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();

        $this->essencial = Product::factory()->create(['slug' => 'essencial']);
        $this->blocoEssencial = FunilBloco::factory()->for(Funil::factory()->create())->for($this->essencial)->create();
    }

    public function test_purchase_starts_the_post_sale_funnel_ahead_of_the_current_one(): void
    {
        $user = User::factory()->create(['phone' => '82999991234']);
        $contato = FunilContato::factory()->create(['telefone' => '5582999991234', 'user_id' => $user->id]);
        $atual = FunilInscricao::factory()->noBloco($this->blocoEssencial)->create([
            'contato_id' => $contato->id,
            'proximo_envio_em' => now()->addDay(),
            'ultima_ordem_enviada' => 2,
        ]);

        $posVenda = Funil::factory()->create();
        $gatilho = FunilGatilho::factory()->compra($this->essencial)->for($posVenda)->create();
        $primeiroBloco = FunilBloco::factory()->for($posVenda)->create(['espera_inicio_seg' => 1800]);

        $this->aprovar(Order::factory()->for($user)->pending()->create(['product_id' => $this->essencial->id]));

        $nova = FunilInscricao::query()->where('funil_id', $posVenda->id)->sole();
        $this->assertSame(FunilInscricaoStatus::NoFunil, $nova->status);
        $this->assertSame($gatilho->id, $nova->gatilho_id);
        $this->assertSame($primeiroBloco->id, $nova->bloco_atual_id);
        $this->assertNotNull($nova->origem_order_id);

        // Paused where it was; it continues when the post-sale ends.
        $atual->refresh();
        $this->assertSame(FunilInscricaoStatus::EmEspera, $atual->status);
        $this->assertSame($this->blocoEssencial->id, $atual->bloco_atual_id);
        $this->assertSame(2, $atual->ultima_ordem_enviada);
    }

    public function test_buyer_without_contact_gets_one_from_the_checkout_phone(): void
    {
        $posVenda = Funil::factory()->create();
        FunilGatilho::factory()->compra($this->essencial)->for($posVenda)->create();
        FunilBloco::factory()->for($posVenda)->create();
        $user = User::factory()->create(['name' => 'Ana Lima', 'phone' => '82988887777']);

        $this->aprovar(Order::factory()->for($user)->pending()->create(['product_id' => $this->essencial->id]));

        $contato = FunilContato::query()->sole();
        $this->assertSame('5582988887777', $contato->telefone);
        $this->assertSame($user->id, $contato->user_id);
        $this->assertSame(FunilInscricaoStatus::NoFunil, $contato->inscricoes()->sole()->status);
    }

    public function test_buying_the_current_block_product_makes_the_contact_due_now(): void
    {
        $user = User::factory()->create(['phone' => null]);
        $inscricao = FunilInscricao::factory()->noBloco($this->blocoEssencial)->create(['proximo_envio_em' => now()->addDay()]);

        $order = Order::factory()->for($user)->pending()->create([
            'product_id' => $this->essencial->id,
            'funil_inscricao_id' => $inscricao->id,
        ]);
        $this->aprovar($order);

        $inscricao->refresh();
        $this->assertTrue($inscricao->proximo_envio_em->lessThanOrEqualTo(now()));
        $this->assertSame($user->id, $inscricao->contato->user_id);
    }

    public function test_unsubscribed_contact_is_not_put_in_post_sale(): void
    {
        $user = User::factory()->create(['phone' => '82999991234']);
        FunilContato::factory()->create(['telefone' => '5582999991234', 'descadastrado_em' => now()]);
        $posVenda = Funil::factory()->create();
        FunilGatilho::factory()->compra($this->essencial)->for($posVenda)->create();

        $this->aprovar(Order::factory()->for($user)->pending()->create(['product_id' => $this->essencial->id]));

        $this->assertDatabaseCount('funil_inscricoes', 0);
    }

    public function test_checkout_keeps_the_funnel_enrollment_on_the_order(): void
    {
        config(['services.mercadopago.access_token' => 'TEST-token']);
        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'pref-1', 'init_point' => 'https://mp.test/pagar'], 201)]);
        $inscricao = FunilInscricao::factory()->noBloco($this->blocoEssencial)->create();

        $this->get(route('checkout.show', ['product' => $this->essencial, 'fi' => $inscricao->id]))
            ->assertOk()
            ->assertSee('name="fi" value="'.$inscricao->id.'"', false);

        $this->post(route('checkout.store', $this->essencial), [
            'name' => 'Maria Souza',
            'email' => 'maria@example.com',
            'email_confirmation' => 'maria@example.com',
            'phone' => '(82) 99999-1234',
            'fi' => $inscricao->id,
        ])->assertRedirect('https://mp.test/pagar');

        $this->assertSame($inscricao->id, Order::query()->sole()->funil_inscricao_id);
    }

    public function test_unknown_enrollment_id_is_ignored_at_checkout(): void
    {
        config(['services.mercadopago.access_token' => 'TEST-token']);
        Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 'pref-1', 'init_point' => 'https://mp.test/pagar'], 201)]);

        $this->post(route('checkout.store', $this->essencial), [
            'name' => 'Maria Souza',
            'email' => 'maria@example.com',
            'email_confirmation' => 'maria@example.com',
            'phone' => '82999991234',
            'fi' => 999,
        ])->assertRedirect('https://mp.test/pagar');

        $this->assertNull(Order::query()->sole()->funil_inscricao_id);
    }

    private function aprovar(Order $order): void
    {
        $order->items()->create(['product_id' => $order->product_id, 'description' => 'Produto', 'unit_price' => $order->amount]);

        app(OrderFulfillment::class)->approve($order);
    }
}
