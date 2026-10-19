<?php

namespace Tests\Feature;

use App\Enums\FunilInscricaoStatus;
use App\Models\Funil;
use App\Models\FunilBloco;
use App\Models\FunilContato;
use App\Models\FunilEnvio;
use App\Models\FunilGatilho;
use App\Models\FunilInscricao;
use App\Models\FunilMensagem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FunilDisparadorTest extends TestCase
{
    use RefreshDatabase;

    private Funil $funil;

    private Product $essencial;

    private Product $bubbles;

    private FunilBloco $bloco1;

    private FunilBloco $bloco2;

    /**
     * What the fake Evolution answers; tests change it.
     *
     * @var array{0: mixed, 1: int}
     */
    private array $evolution = [['key' => ['id' => 'abc']], 201];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.evolution.url' => 'https://evo.test', 'services.evolution.api_key' => 'chave']);
        Http::fake(['evo.test/*' => fn () => Http::response(...$this->evolution)]);

        $this->funil = Funil::factory()->create(['instancia' => 'bubbles']);
        $this->essencial = Product::factory()->create(['name' => 'Essencial', 'slug' => 'essencial', 'price' => 17]);
        $this->bubbles = Product::factory()->create(['name' => 'Bubbles', 'slug' => 'bubbles', 'price' => 67]);

        $this->bloco1 = FunilBloco::factory()->for($this->funil)->for($this->essencial)->create(['posicao' => 1]);
        $this->bloco2 = FunilBloco::factory()->for($this->funil)->for($this->bubbles)->create([
            'posicao' => 2, 'espera_inicio_min' => 1440, 'espera_se_comprou_min' => 30,
        ]);
    }

    public function test_sends_the_next_message_with_offer_price_and_tracked_link(): void
    {
        $offer = ProductOffer::factory()->for($this->essencial)->create(['code' => 'whats', 'price' => 17]);
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id, 'ordem' => 1, 'product_offer_id' => $offer->id]);
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id, 'ordem' => 2, 'espera_min' => 180]);
        $inscricao = $this->inscricao(['nome' => 'MARIA da Silva', 'telefone' => '5582999990000']);

        $this->artisan('funil:disparar')->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://evo.test/message/sendText/bubbles'
            && $request->header('apikey')[0] === 'chave'
            && $request['number'] === '5582999990000'
            && $request['text'] === 'Oi, Maria! Conheça o Essencial por R$ 17,00: '.route('checkout.offer.show', [$this->essencial, $offer]).'?fi='.$inscricao->id);

        $inscricao->refresh();
        $this->assertSame(1, $inscricao->ultima_ordem_enviada);
        $this->assertEqualsWithDelta(now()->addMinutes(180)->timestamp, $inscricao->proximo_envio_em->timestamp, 5);
        $this->assertDatabaseHas('funil_envios', ['inscricao_id' => $inscricao->id, 'status' => FunilEnvio::STATUS_ENVIADO]);
    }

    public function test_without_offer_the_link_is_the_product_checkout(): void
    {
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id, 'texto' => '{preco} {link}']);
        $inscricao = $this->inscricao();

        $this->artisan('funil:disparar')->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request['text'] === '17,00 '.route('checkout.show', $this->essencial).'?fi='.$inscricao->id);
    }

    public function test_after_the_last_message_moves_to_the_next_block(): void
    {
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id, 'ordem' => 1]);
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco2->id, 'ordem' => 1, 'espera_min' => 10]);
        $inscricao = $this->inscricao(estado: ['ultima_ordem_enviada' => 1]);

        $this->artisan('funil:disparar')->assertSuccessful();

        Http::assertNothingSent();
        $inscricao->refresh();
        $this->assertSame($this->bloco2->id, $inscricao->bloco_atual_id);
        $this->assertSame(0, $inscricao->ultima_ordem_enviada);
        $this->assertEqualsWithDelta(now()->addMinutes(1440 + 10)->timestamp, $inscricao->proximo_envio_em->timestamp, 5);
    }

    public function test_contact_who_bought_the_block_product_skips_it_with_the_purchase_wait(): void
    {
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id]);
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['product_id' => $this->bubbles->id]);
        $order->items()->create(['product_id' => $this->essencial->id, 'description' => 'Essencial', 'unit_price' => 12, 'is_order_bump' => true]);
        $inscricao = $this->inscricao(['user_id' => $user->id]);

        $this->artisan('funil:disparar')->assertSuccessful();

        Http::assertNothingSent();
        $inscricao->refresh();
        $this->assertSame($this->bloco2->id, $inscricao->bloco_atual_id);
        $this->assertEqualsWithDelta(now()->addMinutes(30)->timestamp, $inscricao->proximo_envio_em->timestamp, 5);
    }

    public function test_pending_order_does_not_count_as_bought(): void
    {
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id]);
        $user = User::factory()->create();
        Order::factory()->for($user)->pending()->create(['product_id' => $this->essencial->id]);
        $this->inscricao(['user_id' => $user->id]);

        $this->artisan('funil:disparar')->assertSuccessful();

        Http::assertSentCount(1);
    }

    public function test_finishing_the_last_block_starts_the_next_funnel_in_line(): void
    {
        $inscricao = $this->inscricao(estado: ['bloco_atual_id' => $this->bloco2->id]);

        $outroFunil = Funil::factory()->create(['prioridade' => 5]);
        $outroBloco = FunilBloco::factory()->for($outroFunil)->create(['espera_inicio_min' => 60]);
        $baixa = FunilInscricao::factory()->create([
            'contato_id' => $inscricao->contato_id,
            'funil_id' => Funil::factory()->create(['prioridade' => 1])->id,
            'status' => FunilInscricaoStatus::EmEspera,
        ]);
        $alta = FunilInscricao::factory()->create([
            'contato_id' => $inscricao->contato_id,
            'funil_id' => $outroFunil->id,
            'status' => FunilInscricaoStatus::EmEspera,
        ]);

        $this->artisan('funil:disparar')->assertSuccessful();

        $this->assertSame(FunilInscricaoStatus::Concluido, $inscricao->fresh()->status);
        $this->assertNotNull($inscricao->fresh()->finalizado_em);
        $this->assertSame(FunilInscricaoStatus::NoFunil, $alta->fresh()->status);
        $this->assertSame($outroBloco->id, $alta->fresh()->bloco_atual_id);
        $this->assertSame(FunilInscricaoStatus::EmEspera, $baixa->fresh()->status);
    }

    public function test_post_sale_enrollment_goes_before_higher_priority_funnels(): void
    {
        $inscricao = $this->inscricao(estado: ['bloco_atual_id' => $this->bloco2->id]);

        $prioritario = FunilInscricao::factory()->create([
            'contato_id' => $inscricao->contato_id,
            'funil_id' => Funil::factory()->create(['prioridade' => 50])->id,
            'status' => FunilInscricaoStatus::EmEspera,
        ]);
        $posVendaFunil = Funil::factory()->create(['prioridade' => 0]);
        FunilBloco::factory()->for($posVendaFunil)->create();
        $posVenda = FunilInscricao::factory()->create([
            'contato_id' => $inscricao->contato_id,
            'funil_id' => $posVendaFunil->id,
            'gatilho_id' => FunilGatilho::factory()->compra()->for($posVendaFunil)->create()->id,
            'status' => FunilInscricaoStatus::EmEspera,
        ]);

        $this->artisan('funil:disparar')->assertSuccessful();

        $this->assertSame(FunilInscricaoStatus::NoFunil, $posVenda->fresh()->status);
        $this->assertSame(FunilInscricaoStatus::EmEspera, $prioritario->fresh()->status);
    }

    public function test_outside_sending_hours_nothing_happens(): void
    {
        $this->travelTo(now()->setTime(22, 30));
        $this->funil->update(['hora_inicio_envio' => 8, 'hora_fim_envio' => 21]);
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id]);
        $inscricao = $this->inscricao();

        $this->artisan('funil:disparar')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, $inscricao->fresh()->ultima_ordem_enviada);
    }

    public function test_inactive_block_releases_its_contacts_to_the_next_block(): void
    {
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id]);
        $this->bloco1->update(['ativo' => false]);
        $inscricao = $this->inscricao();

        $this->artisan('funil:disparar')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame($this->bloco2->id, $inscricao->fresh()->bloco_atual_id);
    }

    public function test_message_already_sent_is_never_sent_again(): void
    {
        $mensagem = FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id, 'ordem' => 1]);
        $inscricao = $this->inscricao();
        FunilEnvio::query()->create(['inscricao_id' => $inscricao->id, 'mensagem_id' => $mensagem->id, 'status' => FunilEnvio::STATUS_ENVIADO]);

        $this->artisan('funil:disparar')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(1, $inscricao->fresh()->ultima_ordem_enviada);
    }

    public function test_number_refused_by_evolution_is_retried_once_then_skipped(): void
    {
        $this->evolution = [['response' => ['message' => [['exists' => false]]]], 400];
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id, 'ordem' => 1]);
        $inscricao = $this->inscricao();

        $this->artisan('funil:disparar')->assertSuccessful();

        $inscricao->refresh();
        $this->assertSame(0, $inscricao->ultima_ordem_enviada);
        $this->assertTrue($inscricao->proximo_envio_em->isFuture());
        $this->assertDatabaseHas('funil_envios', ['inscricao_id' => $inscricao->id, 'status' => FunilEnvio::STATUS_ERRO]);

        $this->travel(31)->minutes();
        $this->artisan('funil:disparar')->assertSuccessful();

        $this->assertSame(1, $inscricao->fresh()->ultima_ordem_enviada);
    }

    public function test_evolution_down_stops_the_run_without_moving_anyone(): void
    {
        $this->evolution = ['erro', 500];
        FunilMensagem::factory()->create(['bloco_id' => $this->bloco1->id]);
        $inscricao = $this->inscricao();

        $this->artisan('funil:disparar')->assertFailed();

        $inscricao->refresh();
        $this->assertSame(0, $inscricao->ultima_ordem_enviada);
        $this->assertNull($inscricao->processando_desde);
        $this->assertDatabaseCount('funil_envios', 0);
    }

    /**
     * @param  array<string, mixed>  $contato
     * @param  array<string, mixed>  $estado
     */
    private function inscricao(array $contato = [], array $estado = []): FunilInscricao
    {
        return FunilInscricao::factory()
            ->noBloco($this->bloco1)
            ->create(['contato_id' => FunilContato::factory()->create($contato)->id, ...$estado]);
    }
}
