<?php

namespace App\Services\Funil;

use App\Enums\FunilGatilhoTipo;
use App\Enums\FunilInscricaoStatus;
use App\Models\FunilContato;
use App\Models\FunilGatilho;
use App\Models\FunilInscricao;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * What comes in: the landing form and the WhatsApp messages Evolution forwards.
 * Form → "posso te enviar o ebook?"; the reply → ebook + enters the funnel.
 */
class FunilRecepcao
{
    public const MSG_DESCADASTRO = 'Pronto! Você não vai mais receber mensagens nossas por aqui. 🙏';

    private const PALAVRAS_SAIR = '/^(sair|parar|pare|stop|cancelar)$/i';

    public function __construct(
        private EvolutionApi $evolution,
        private FunilAgenda $agenda,
    ) {}

    public function formulario(FunilGatilho $gatilho, string $telefone, string $nome, ?string $email = null): void
    {
        // Same number on the same form within the same minute: a double click.
        if (! $this->primeiraVez('form-'.$telefone.'-'.$gatilho->funil_id.'-'.now()->format('YmdHi'), $telefone)) {
            return;
        }

        $contato = FunilContato::query()->firstOrCreate(['telefone' => $telefone], ['nome' => $nome, 'email' => $email]);
        $contato->update(array_filter(['nome' => $nome, 'email' => $email]));
        $this->vincularAluno($contato);

        if ($contato->isDescadastrado()) {
            return;
        }

        $inscricao = FunilInscricao::query()->firstOrCreate(
            ['contato_id' => $contato->id, 'funil_id' => $gatilho->funil_id],
            ['gatilho_id' => $gatilho->id, 'status' => FunilInscricaoStatus::AguardandoConfirmacao],
        );

        if ($inscricao->status !== FunilInscricaoStatus::AguardandoConfirmacao) {
            return;
        }

        $this->enviarSemTravar($gatilho->funil->instancia, $telefone, FunilTexto::montar((string) $gatilho->opcao('msg_confirmacao'), [
            'primeiro_nome' => $contato->primeiroNome(),
        ]));
    }

    public function mensagemRecebida(string $instancia, string $telefone, string $texto, string $messageId, ?string $nome = null): void
    {
        if (! $this->primeiraVez($messageId, $telefone)) {
            return;
        }

        // Only people who came through a funnel are answered; anyone else is a normal chat.
        $contato = FunilContato::query()->where('telefone', $telefone)->first();

        if (! $contato) {
            return;
        }

        $contato->update(array_filter([
            'ultima_interacao_em' => now(),
            'nome' => blank($contato->nome) ? $nome : null,
        ]));

        if (preg_match(self::PALAVRAS_SAIR, trim($texto))) {
            if (! $contato->isDescadastrado()) {
                $this->agenda->descadastrar($contato);
                $this->enviarSemTravar($instancia, $telefone, self::MSG_DESCADASTRO);
            }

            return;
        }

        if ($contato->isDescadastrado()) {
            return;
        }

        $inscricao = $contato->inscricoes()
            ->where('status', FunilInscricaoStatus::AguardandoConfirmacao)
            ->whereHas('funil', fn ($funis) => $funis->where('ativo', true)->where('instancia', $instancia))
            ->whereHas('gatilho', fn ($gatilhos) => $gatilhos->where('tipo', FunilGatilhoTipo::Formulario))
            ->with(['funil', 'gatilho'])
            ->latest('id')
            ->first();

        if (! $inscricao) {
            return;
        }

        // If the ebook doesn't go out, the contact stays waiting and the next reply tries again.
        $this->evolution->enviarTexto($instancia, $telefone, FunilTexto::montar((string) $inscricao->gatilho->opcao('msg_ebook'), [
            'primeiro_nome' => $contato->primeiroNome(),
            'link_ebook' => (string) $inscricao->gatilho->opcao('link_ebook'),
        ]));

        $inscricao->update(['confirmado_em' => now()]);
        $this->agenda->colocarNoFunil($inscricao);
    }

    /**
     * Records the incoming message id; false when it was already handled.
     */
    private function primeiraVez(string $messageId, string $telefone): bool
    {
        return DB::table('funil_msg_processadas')->insertOrIgnore([
            'message_id' => mb_substr($messageId, 0, 100),
            'telefone' => $telefone,
            'recebida_em' => now(),
        ]) > 0;
    }

    private function vincularAluno(FunilContato $contato): void
    {
        if ($contato->user_id === null && filled($contato->email)) {
            $contato->update(['user_id' => User::query()->where('email', mb_strtolower($contato->email))->value('id')]);
        }
    }

    /**
     * The form visitor still goes to the thank-you page when WhatsApp fails.
     */
    private function enviarSemTravar(string $instancia, string $telefone, string $texto): void
    {
        try {
            $this->evolution->enviarTexto($instancia, $telefone, $texto);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
