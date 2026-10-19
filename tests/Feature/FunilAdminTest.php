<?php

namespace Tests\Feature;

use App\Enums\FunilGatilhoTipo;
use App\Enums\FunilInscricaoStatus;
use App\Models\Funil;
use App\Models\FunilBloco;
use App\Models\FunilGatilho;
use App\Models\FunilInscricao;
use App\Models\FunilMensagem;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunilAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_students_cannot_open_the_funnels(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.funis.index'))->assertForbidden();
    }

    public function test_admin_creates_a_funnel(): void
    {
        $this->actingAs($this->admin)->get(route('admin.funis.create'))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.funis.store'), [
            'nome' => 'Ebook Arte com Balões',
            'instancia' => 'bubbles',
            'prioridade' => '',
            'hora_inicio_envio' => 8,
            'hora_fim_envio' => 21,
            'ativo' => '1',
        ])->assertSessionHasNoErrors();

        $funil = Funil::query()->sole();
        $this->assertSame('ebook-arte-com-baloes', $funil->slug);
        $this->assertSame(0, $funil->prioridade);
        $this->assertTrue($funil->ativo);

        $this->actingAs($this->admin)->get(route('admin.funis.index'))->assertOk()->assertSee('Ebook Arte com Balões');
    }

    public function test_sending_hours_must_end_after_they_start(): void
    {
        $this->actingAs($this->admin)->post(route('admin.funis.store'), [
            'nome' => 'Funil', 'instancia' => 'bubbles', 'hora_inicio_envio' => 21, 'hora_fim_envio' => 8,
        ])->assertSessionHasErrors('hora_fim_envio');
    }

    public function test_panel_shows_blocks_messages_and_preview(): void
    {
        $funil = Funil::factory()->create();
        $product = Product::factory()->create(['name' => 'Treinamento Essencial', 'slug' => 'essencial', 'price' => 17]);
        $offer = ProductOffer::factory()->for($product)->create(['name' => 'Whats 17', 'code' => 'whats', 'price' => 17]);
        $bloco = FunilBloco::factory()->for($funil)->for($product)->create();
        FunilMensagem::factory()->create(['bloco_id' => $bloco->id, 'product_offer_id' => $offer->id, 'espera_seg' => 72000]);
        FunilGatilho::factory()->for($funil)->create();

        $this->actingAs($this->admin)->get(route('admin.funis.edit', $funil))
            ->assertOk()
            ->assertSee('Treinamento Essencial')
            ->assertSee('Whats 17 · R$ 17,00')
            ->assertSee('Oi, Maria! Conheça o Treinamento Essencial por R$ 17,00: '.route('checkout.offer.show', [$product, $offer]).'?fi=123')
            ->assertSee('value="20"', false)
            ->assertSee(route('funil.formulario'));
    }

    public function test_form_trigger_requires_its_texts_and_is_unique_per_funnel(): void
    {
        $funil = Funil::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.funil-gatilhos.store', $funil), ['tipo' => 'formulario'])
            ->assertSessionHasErrorsIn('gatilho', ['msg_confirmacao', 'msg_ebook', 'link_ebook', 'pagina_obrigado']);

        $dados = [
            'tipo' => 'formulario',
            'msg_confirmacao' => 'Posso te enviar?',
            'msg_ebook' => 'Aqui: {link_ebook}',
            'link_ebook' => 'https://drive.google.com/x',
            'pagina_obrigado' => 'https://julianabaloes.com.br/obrigado-01/',
            'ativo' => '1',
        ];

        $this->actingAs($this->admin)->post(route('admin.funil-gatilhos.store', $funil), $dados)->assertSessionHasNoErrors();
        $this->assertSame('Aqui: {link_ebook}', $funil->gatilhoFormulario()->opcao('msg_ebook'));

        $this->actingAs($this->admin)->post(route('admin.funil-gatilhos.store', $funil), $dados)
            ->assertSessionHasErrorsIn('gatilho', ['tipo']);
    }

    public function test_purchase_trigger_keeps_only_the_product(): void
    {
        $funil = Funil::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.funil-gatilhos.store', $funil), [
            'tipo' => 'compra_produto',
            'product_id' => $product->id,
            'msg_ebook' => 'ignorado',
            'ativo' => '1',
        ])->assertSessionHasNoErrors();

        $gatilho = FunilGatilho::query()->sole();
        $this->assertSame(FunilGatilhoTipo::CompraProduto, $gatilho->tipo);
        $this->assertSame($product->id, $gatilho->product_id);
        $this->assertNull($gatilho->config);
    }

    public function test_blocks_are_added_at_the_end_and_saved_with_wait_units(): void
    {
        $funil = Funil::factory()->create();
        $produto = Product::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.funil-blocos.store', $funil), ['product_id' => $produto->id]);
        $this->actingAs($this->admin)->post(route('admin.funil-blocos.store', $funil), ['product_id' => $produto->id]);

        [$primeiro, $segundo] = $funil->blocos()->get()->all();
        $this->assertSame([1, 2], [$primeiro->posicao, $segundo->posicao]);
        $this->assertSame(0, $primeiro->espera_inicio_seg);
        $this->assertSame([86400, 1800], [$segundo->espera_inicio_seg, $segundo->espera_se_comprou_seg]);

        $this->actingAs($this->admin)->put(route('admin.funil-blocos.update', $segundo), [
            'product_id' => $produto->id,
            'espera_inicio_valor' => 2, 'espera_inicio_unidade' => 'dias',
            'espera_compra_valor' => 3, 'espera_compra_unidade' => 'horas',
            'ativo' => '1',
        ])->assertRedirect(route('admin.funis.edit', $funil).'#bloco-'.$segundo->id);

        $segundo->refresh();
        $this->assertSame(172800, $segundo->espera_inicio_seg);
        $this->assertNull($segundo->espera_se_comprou_seg);
    }

    public function test_deactivating_a_block_releases_its_contacts(): void
    {
        $bloco = FunilBloco::factory()->create();
        $inscricao = FunilInscricao::factory()->noBloco($bloco)->create(['proximo_envio_em' => now()->addDays(3)]);

        $this->actingAs($this->admin)->put(route('admin.funil-blocos.update', $bloco), [
            'product_id' => $bloco->product_id,
            'espera_inicio_valor' => 0, 'espera_inicio_unidade' => 'min',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($bloco->fresh()->ativo);
        $this->assertTrue($inscricao->fresh()->proximo_envio_em->lessThanOrEqualTo(now()));
    }

    public function test_block_with_contacts_cannot_be_deleted(): void
    {
        $bloco = FunilBloco::factory()->create();
        FunilInscricao::factory()->noBloco($bloco)->create();

        $this->actingAs($this->admin)->delete(route('admin.funil-blocos.destroy', $bloco))->assertSessionHas('error');
        $this->assertModelExists($bloco);
    }

    public function test_blocks_reorder_by_drag_and_by_arrows(): void
    {
        $funil = Funil::factory()->create();
        $a = FunilBloco::factory()->for($funil)->create(['posicao' => 1]);
        $b = FunilBloco::factory()->for($funil)->create(['posicao' => 2]);
        $c = FunilBloco::factory()->for($funil)->create(['posicao' => 3]);

        $this->actingAs($this->admin)->putJson(route('admin.funil-blocos.reorder', $funil), ['ids' => [$c->id, $a->id, $b->id]])->assertOk();
        $this->assertSame([$c->id, $a->id, $b->id], $funil->blocos()->pluck('id')->all());

        $this->actingAs($this->admin)->patch(route('admin.funil-blocos.move', $b), ['direcao' => 'cima']);
        $this->assertSame([$c->id, $b->id, $a->id], $funil->blocos()->pluck('id')->all());

        $this->actingAs($this->admin)->putJson(route('admin.funil-blocos.reorder', $funil), ['ids' => [$a->id, $b->id]])->assertStatus(422);
    }

    public function test_messages_are_created_saved_reordered_and_deleted(): void
    {
        $product = Product::factory()->create();
        $offer = ProductOffer::factory()->for($product)->create();
        $bloco = FunilBloco::factory()->for($product)->create();

        $this->actingAs($this->admin)->post(route('admin.funil-mensagens.store', $bloco));
        $this->actingAs($this->admin)->post(route('admin.funil-mensagens.store', $bloco));

        [$primeira, $segunda] = $bloco->mensagens()->get()->all();
        $this->assertSame($offer->id, $primeira->product_offer_id);
        $this->assertSame([0, 86400], [$primeira->espera_seg, $segunda->espera_seg]);

        $this->actingAs($this->admin)->put(route('admin.funil-mensagens.update', $segunda), [
            'texto' => 'Última chance, {primeiro_nome}!',
            'espera_valor' => 45, 'espera_unidade' => 'seg',
            'product_offer_id' => '',
            'ativo' => '1',
        ])->assertSessionHasNoErrors();

        $segunda->refresh();
        $this->assertSame(45, $segunda->espera_seg);
        $this->assertNull($segunda->product_offer_id);

        $this->actingAs($this->admin)->patch(route('admin.funil-mensagens.move', $segunda), ['direcao' => 'cima']);
        $this->assertSame([$segunda->id, $primeira->id], $bloco->mensagens()->pluck('id')->all());

        $this->actingAs($this->admin)->putJson(route('admin.funil-mensagens.reorder', $bloco), ['ids' => [$primeira->id, $segunda->id]])->assertOk();
        $this->assertSame([1, 2], [$primeira->fresh()->ordem, $segunda->fresh()->ordem]);

        $this->actingAs($this->admin)->delete(route('admin.funil-mensagens.destroy', $primeira));
        $this->assertModelMissing($primeira);
    }

    public function test_deactivating_a_funnel_pauses_its_contacts_and_lets_other_funnels_send(): void
    {
        $funil = Funil::factory()->create();
        $inscricao = FunilInscricao::factory()->noBloco(FunilBloco::factory()->for($funil)->create())->create();
        $outroBloco = FunilBloco::factory()->create();
        $outra = FunilInscricao::factory()->create([
            'contato_id' => $inscricao->contato_id,
            'funil_id' => $outroBloco->funil_id,
            'status' => FunilInscricaoStatus::EmEspera,
        ]);

        $this->actingAs($this->admin)->put(route('admin.funis.update', $funil), [
            'nome' => $funil->nome, 'slug' => $funil->slug, 'instancia' => 'bubbles', 'prioridade' => 0,
            'hora_inicio_envio' => 8, 'hora_fim_envio' => 21,
        ])->assertSessionHasNoErrors();

        $this->assertSame(FunilInscricaoStatus::EmEspera, $inscricao->fresh()->status);
        $this->assertSame(FunilInscricaoStatus::NoFunil, $outra->fresh()->status);
    }

    public function test_funnel_with_contacts_cannot_be_deleted(): void
    {
        $inscricao = FunilInscricao::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.funis.destroy', $inscricao->funil_id))->assertSessionHas('error');
        $this->assertModelExists($inscricao->funil);

        $vazio = Funil::factory()->create();
        $this->actingAs($this->admin)->delete(route('admin.funis.destroy', $vazio))->assertRedirect(route('admin.funis.index'));
        $this->assertModelMissing($vazio);
    }

    public function test_settings_show_the_evolution_webhook_and_keep_the_api_key_secret(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(route('webhooks.evolution').'?token=', false);

        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'support_email' => 'suporte@example.com',
            'company_name' => 'Juliana Balões',
            'evolution_url' => 'https://evo.example.com/',
            'evolution_api_key' => 'chave-global-123456',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://evo.example.com', Setting::value('evolution_url'));
        $this->assertSame('chave-global-123456', Setting::value('evolution_api_key'));
        $this->assertDatabaseMissing('settings', ['value' => 'chave-global-123456']);
    }
}
