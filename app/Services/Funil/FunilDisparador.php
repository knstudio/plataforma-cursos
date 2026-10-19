<?php

namespace App\Services\Funil;

use App\Enums\FunilInscricaoStatus;
use App\Models\FunilBloco;
use App\Models\FunilEnvio;
use App\Models\FunilInscricao;
use App\Models\FunilMensagem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Runs every 5 minutes (funil:disparar). For each enrollment that is due, inside
 * its funnel's sending hours: advances when the contact already has the block's
 * product, sends the next message, or moves on to the next block.
 */
class FunilDisparador
{
    /**
     * An enrollment locked longer than this is considered abandoned by a crashed run.
     */
    private const TRAVA_MINUTOS = 15;

    /**
     * A message refused by Evolution is tried again after this, once.
     */
    private const NOVA_TENTATIVA_MINUTOS = 30;

    public function __construct(
        private EvolutionApi $evolution,
        private FunilAgenda $agenda,
    ) {}

    /**
     * Returns how many enrollments were handled. Stops with EvolutionException when
     * Evolution itself is unreachable or misconfigured (nobody is skipped then).
     */
    public function processar(int $limite = 30): int
    {
        $agora = now();

        $ids = $this->devidas($agora)
            ->whereHas('funil', fn (Builder $funis) => $funis
                ->where('ativo', true)
                ->where('hora_inicio_envio', '<=', $agora->hour)
                ->where('hora_fim_envio', '>', $agora->hour))
            ->orderBy('proximo_envio_em')
            ->limit($limite)
            ->pluck('id');

        $processadas = 0;

        foreach ($ids as $id) {
            // Claimed one by one, so two runs at the same time never send the same message.
            if (! $this->devidas($agora)->whereKey($id)->update(['processando_desde' => $agora])) {
                continue;
            }

            $inscricao = FunilInscricao::query()
                ->with(['contato.user', 'funil', 'blocoAtual.product', 'blocoAtual.mensagens.offer.product'])
                ->findOrFail($id);

            try {
                $this->processarInscricao($inscricao);
                $processadas++;
            } finally {
                FunilInscricao::query()->whereKey($id)->update(['processando_desde' => null]);
            }
        }

        return $processadas;
    }

    /**
     * @return Builder<FunilInscricao>
     */
    private function devidas(CarbonInterface $agora): Builder
    {
        return FunilInscricao::query()
            ->where('status', FunilInscricaoStatus::NoFunil)
            ->where('proximo_envio_em', '<=', $agora)
            ->where(fn (Builder $query) => $query
                ->whereNull('processando_desde')
                ->orWhere('processando_desde', '<', $agora->copy()->subMinutes(self::TRAVA_MINUTOS)));
    }

    private function processarInscricao(FunilInscricao $inscricao): void
    {
        $bloco = $inscricao->blocoAtual;

        if (! $bloco) {
            $this->agenda->concluir($inscricao);

            return;
        }

        // Rule: whoever has the product never gets an offer of it, in any funnel.
        if ($inscricao->contato->temProduto($bloco->product_id)) {
            $this->avancar($inscricao, $bloco, comprou: true);

            return;
        }

        $mensagem = $bloco->ativo ? $this->proximaMensagem($bloco, $inscricao->ultima_ordem_enviada) : null;

        if (! $mensagem) {
            $this->avancar($inscricao, $bloco, comprou: false);

            return;
        }

        $this->enviar($inscricao, $bloco, $mensagem);
    }

    private function enviar(FunilInscricao $inscricao, FunilBloco $bloco, FunilMensagem $mensagem): void
    {
        $envio = FunilEnvio::query()->firstOrNew(['inscricao_id' => $inscricao->id, 'mensagem_id' => $mensagem->id]);

        // Already sent before the messages were reordered: never twice.
        if ($envio->exists && $envio->status === FunilEnvio::STATUS_ENVIADO) {
            $this->agendarProxima($inscricao, $bloco, $mensagem);

            return;
        }

        $envio->fill(['bloco_id' => $bloco->id, 'product_offer_id' => $mensagem->product_offer_id]);

        try {
            $this->evolution->enviarTexto(
                $inscricao->funil->instancia,
                $inscricao->contato->telefone,
                FunilTexto::mensagem($mensagem, $bloco, $inscricao->contato->primeiroNome(), $inscricao->id),
            );
        } catch (EvolutionException $exception) {
            if (! $exception->doContato) {
                throw $exception;
            }

            $jaFalhou = $envio->exists && $envio->status === FunilEnvio::STATUS_ERRO;
            $envio->fill(['status' => FunilEnvio::STATUS_ERRO, 'detalhe' => Str::limit($exception->getMessage(), 250)])->save();

            // Second refusal: skip this message and go on with the sequence.
            if ($jaFalhou) {
                $this->agendarProxima($inscricao, $bloco, $mensagem);
            } else {
                $inscricao->update(['proximo_envio_em' => now()->addMinutes(self::NOVA_TENTATIVA_MINUTOS)]);
            }

            return;
        }

        $envio->fill(['status' => FunilEnvio::STATUS_ENVIADO, 'detalhe' => null])->save();

        $this->agendarProxima($inscricao, $bloco, $mensagem);
    }

    private function agendarProxima(FunilInscricao $inscricao, FunilBloco $bloco, FunilMensagem $mensagem): void
    {
        $seguinte = $this->proximaMensagem($bloco, $mensagem->ordem);

        $inscricao->update([
            'ultima_ordem_enviada' => $mensagem->ordem,
            'proximo_envio_em' => now()->addSeconds($seguinte?->espera_seg ?? 0),
        ]);
    }

    /**
     * Next active block. $comprou: the contact has this block's product, so the
     * next one uses its "se comprou" wait.
     */
    private function avancar(FunilInscricao $inscricao, FunilBloco $bloco, bool $comprou): void
    {
        $proximo = FunilBloco::query()
            ->where('funil_id', $inscricao->funil_id)
            ->where('ativo', true)
            ->where(fn (Builder $query) => $query
                ->where('posicao', '>', $bloco->posicao)
                ->orWhere(fn (Builder $mesma) => $mesma->where('posicao', $bloco->posicao)->where('id', '>', $bloco->id)))
            ->orderBy('posicao')
            ->orderBy('id')
            ->first();

        if (! $proximo) {
            $this->agenda->concluir($inscricao);

            return;
        }

        $espera = $comprou
            ? ($proximo->espera_se_comprou_seg ?? $proximo->espera_inicio_seg)
            : $proximo->espera_inicio_seg;

        $inscricao->update([
            'bloco_atual_id' => $proximo->id,
            'ultima_ordem_enviada' => 0,
            'proximo_envio_em' => now()->addSeconds($espera + $proximo->esperaPrimeiraMensagem()),
        ]);
    }

    private function proximaMensagem(FunilBloco $bloco, int $depoisDaOrdem): ?FunilMensagem
    {
        return $bloco->mensagens
            ->filter(fn (FunilMensagem $mensagem) => $mensagem->ativo && $mensagem->ordem > $depoisDaOrdem)
            ->sortBy([['ordem', 'asc'], ['id', 'asc']])
            ->first();
    }
}
