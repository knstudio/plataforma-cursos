<?php

namespace Tests\Feature;

use App\Enums\FunilInscricaoStatus;
use App\Models\Funil;
use App\Models\FunilBloco;
use App\Models\FunilContato;
use App\Models\FunilGatilho;
use App\Models\FunilInscricao;
use App\Models\Setting;
use App\Services\Funil\FunilRecepcao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FunilRecepcaoTest extends TestCase
{
    use RefreshDatabase;

    private Funil $funil;

    private FunilGatilho $formulario;

    private FunilBloco $bloco;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.evolution.url' => 'https://evo.test', 'services.evolution.api_key' => 'chave']);
        Http::fake(['evo.test/*' => Http::response([], 201)]);
        Setting::store(['evolution_webhook_token' => 'token-secreto']);

        $this->funil = Funil::factory()->create(['slug' => 'ebook-arte-baloes', 'instancia' => 'bubbles']);
        $this->formulario = FunilGatilho::factory()->for($this->funil)->create();
        $this->bloco = FunilBloco::factory()->for($this->funil)->create(['espera_inicio_seg' => 3600]);
    }

    public function test_form_creates_the_contact_and_asks_for_confirmation(): void
    {
        $this->post(route('funil.formulario'), [
            'funil' => 'ebook-arte-baloes',
            'nome' => '  maria   DA silva ',
            'whatsApp' => '(82) 99999-1234',
        ])->assertRedirect('https://julianabaloes.com.br/obrigado-01/');

        $contato = FunilContato::query()->sole();
        $this->assertSame('5582999991234', $contato->telefone);
        $this->assertSame('maria DA silva', $contato->nome);
        $this->assertSame(FunilInscricaoStatus::AguardandoConfirmacao, $contato->inscricoes()->sole()->status);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://evo.test/message/sendText/bubbles'
            && $request['number'] === '5582999991234'
            && $request['text'] === 'Oi, Maria! Posso te enviar o ebook?');
    }

    public function test_form_with_invalid_phone_goes_back_to_the_landing(): void
    {
        $this->from('https://julianabaloes.com.br/landing-page/')
            ->post(route('funil.formulario'), ['funil' => 'ebook-arte-baloes', 'nome' => 'Maria', 'whatsApp' => '1234'])
            ->assertRedirect('https://julianabaloes.com.br/landing-page/?erro=telefone');

        $this->assertDatabaseCount('funil_contatos', 0);
        Http::assertNothingSent();
    }

    public function test_form_of_unknown_or_inactive_funnel_is_not_found(): void
    {
        $this->post(route('funil.formulario'), ['funil' => 'nao-existe', 'nome' => 'Maria', 'whatsApp' => '82999991234'])->assertNotFound();

        $this->funil->update(['ativo' => false]);
        $this->post(route('funil.formulario'), ['funil' => 'ebook-arte-baloes', 'nome' => 'Maria', 'whatsApp' => '82999991234'])->assertNotFound();
    }

    public function test_double_submit_sends_the_confirmation_once(): void
    {
        $dados = ['funil' => 'ebook-arte-baloes', 'nome' => 'Maria', 'whatsApp' => '82999991234'];

        $this->post(route('funil.formulario'), $dados);
        $this->post(route('funil.formulario'), $dados);

        Http::assertSentCount(1);
    }

    public function test_whatsapp_reply_sends_the_ebook_and_enters_the_first_block(): void
    {
        $inscricao = $this->aguardando('5582999991234');

        $this->webhook('5582999991234', 'Sim, pode!')->assertOk();

        Http::assertSent(fn (Request $request) => $request['text'] === 'Aqui está, Maria: https://drive.google.com/ebook');

        $inscricao->refresh();
        $this->assertSame(FunilInscricaoStatus::NoFunil, $inscricao->status);
        $this->assertSame($this->bloco->id, $inscricao->bloco_atual_id);
        $this->assertNotNull($inscricao->confirmado_em);
        $this->assertEqualsWithDelta(now()->addHour()->timestamp, $inscricao->proximo_envio_em->timestamp, 5);
    }

    public function test_jid_without_the_ninth_digit_and_lid_are_understood(): void
    {
        $inscricao = $this->aguardando('5582999991234');

        $this->post(route('webhooks.evolution', ['token' => 'token-secreto']), [
            'event' => 'messages.upsert',
            'instance' => 'bubbles',
            'data' => [
                'key' => ['id' => 'MSG1', 'fromMe' => false, 'remoteJid' => '123456789@lid', 'remoteJidAlt' => '558299991234@s.whatsapp.net'],
                'message' => ['extendedTextMessage' => ['text' => 'quero']],
            ],
        ])->assertOk();

        $this->assertSame(FunilInscricaoStatus::NoFunil, $inscricao->fresh()->status);
    }

    public function test_second_funnel_waits_while_another_is_sending(): void
    {
        $contato = FunilContato::factory()->create(['telefone' => '5582999991234']);
        $ativa = FunilInscricao::factory()->noBloco(FunilBloco::factory()->create())->create(['contato_id' => $contato->id]);
        $inscricao = $this->aguardando('5582999991234');

        $this->webhook('5582999991234', 'sim')->assertOk();

        $this->assertSame(FunilInscricaoStatus::EmEspera, $inscricao->fresh()->status);
        $this->assertSame(FunilInscricaoStatus::NoFunil, $ativa->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_sair_unsubscribes_from_every_funnel(): void
    {
        $inscricao = $this->aguardando('5582999991234');
        $outra = FunilInscricao::factory()->noBloco(FunilBloco::factory()->create())->create(['contato_id' => $inscricao->contato_id]);

        $this->webhook('5582999991234', ' SAIR ')->assertOk();

        $this->assertNotNull($inscricao->contato->fresh()->descadastrado_em);
        $this->assertSame(FunilInscricaoStatus::Descadastrado, $inscricao->fresh()->status);
        $this->assertSame(FunilInscricaoStatus::Descadastrado, $outra->fresh()->status);
        Http::assertSent(fn (Request $request) => $request['text'] === FunilRecepcao::MSG_DESCADASTRO);

        // Gone for good: filling the form again does nothing.
        $this->post(route('funil.formulario'), ['funil' => 'ebook-arte-baloes', 'nome' => 'Maria', 'whatsApp' => '82999991234']);
        Http::assertSentCount(1);
    }

    public function test_same_message_delivered_twice_is_handled_once(): void
    {
        $this->aguardando('5582999991234');

        $this->webhook('5582999991234', 'sim', 'MSG-REPETIDA');
        $this->webhook('5582999991234', 'sim', 'MSG-REPETIDA');

        Http::assertSentCount(1);
    }

    public function test_messages_from_unknown_numbers_my_own_and_groups_are_ignored(): void
    {
        $this->webhook('5582988887777', 'oi')->assertOk();

        $this->aguardando('5582999991234');
        $this->post(route('webhooks.evolution', ['token' => 'token-secreto']), [
            'event' => 'messages.upsert',
            'instance' => 'bubbles',
            'data' => ['key' => ['id' => 'X1', 'fromMe' => true, 'remoteJid' => '5582999991234@s.whatsapp.net'], 'message' => ['conversation' => 'oi']],
        ])->assertOk();
        $this->post(route('webhooks.evolution', ['token' => 'token-secreto']), [
            'event' => 'messages.upsert',
            'instance' => 'bubbles',
            'data' => ['key' => ['id' => 'X2', 'fromMe' => false, 'remoteJid' => '120363@g.us'], 'message' => ['conversation' => 'oi']],
        ])->assertOk();

        Http::assertNothingSent();
    }

    public function test_webhook_requires_the_token(): void
    {
        $this->post(route('webhooks.evolution'), ['event' => 'messages.upsert'])->assertUnauthorized();
        $this->post(route('webhooks.evolution', ['token' => 'errado']), ['event' => 'messages.upsert'])->assertUnauthorized();
    }

    private function aguardando(string $telefone): FunilInscricao
    {
        return FunilInscricao::factory()->create([
            'contato_id' => (FunilContato::query()->where('telefone', $telefone)->first()
                ?? FunilContato::factory()->create(['telefone' => $telefone, 'nome' => 'Maria Souza']))->id,
            'funil_id' => $this->funil->id,
            'gatilho_id' => $this->formulario->id,
        ]);
    }

    private function webhook(string $telefone, string $texto, ?string $id = null): TestResponse
    {
        return $this->post(route('webhooks.evolution', ['token' => 'token-secreto']), [
            'event' => 'messages.upsert',
            'instance' => 'bubbles',
            'data' => [
                'key' => ['id' => $id ?? uniqid('MSG'), 'fromMe' => false, 'remoteJid' => $telefone.'@s.whatsapp.net'],
                'pushName' => 'Maria',
                'message' => ['conversation' => $texto],
            ],
        ]);
    }
}
