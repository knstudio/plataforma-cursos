<?php

namespace App\Services\Funil;

use App\Enums\FunilInscricaoStatus;
use App\Models\Funil;
use App\Models\FunilContato;
use App\Models\FunilInscricao;
use Illuminate\Support\Facades\DB;

/**
 * Which funnel each contact is receiving. A contact can be in many funnels but
 * only one sends at a time (status no_funil); the others wait (em_espera) until
 * it ends. A purchase that starts a post-sale funnel takes the turn right away.
 */
class FunilAgenda
{
    /**
     * Puts the enrollment in the sending sequence, or in line when another funnel
     * of the contact is sending. $prioritaria (post-sale) pauses the current one instead.
     */
    public function colocarNoFunil(FunilInscricao $inscricao, bool $prioritaria = false): void
    {
        DB::transaction(function () use ($inscricao, $prioritaria): void {
            $ativas = FunilInscricao::query()
                ->where('contato_id', $inscricao->contato_id)
                ->where('status', FunilInscricaoStatus::NoFunil)
                ->whereKeyNot($inscricao->id)
                ->lockForUpdate()
                ->get();

            if ($ativas->isNotEmpty() && ! $prioritaria) {
                $inscricao->update(['status' => FunilInscricaoStatus::EmEspera, 'proximo_envio_em' => null]);

                return;
            }

            // Paused ones keep their pointer and continue from there later.
            foreach ($ativas as $ativa) {
                $ativa->update(['status' => FunilInscricaoStatus::EmEspera, 'proximo_envio_em' => null]);
            }

            $this->iniciar($inscricao);
        });
    }

    public function concluir(FunilInscricao $inscricao): void
    {
        $inscricao->update([
            'status' => FunilInscricaoStatus::Concluido,
            'proximo_envio_em' => null,
            'finalizado_em' => now(),
        ]);

        $this->ativarProxima($inscricao->contato);
    }

    /**
     * When nothing is sending to the contact, the next one in line starts:
     * post-sale first, then the funnel priority, then who entered first.
     */
    public function ativarProxima(FunilContato $contato): void
    {
        if ($contato->isDescadastrado() || $contato->inscricoes()->where('status', FunilInscricaoStatus::NoFunil)->exists()) {
            return;
        }

        $proxima = $contato->inscricoes()
            ->where('status', FunilInscricaoStatus::EmEspera)
            ->whereHas('funil', fn ($funis) => $funis->where('ativo', true))
            ->with(['gatilho', 'funil'])
            ->get()
            ->sort(fn (FunilInscricao $a, FunilInscricao $b) => [$b->isPosVenda(), $b->funil->prioridade, $a->id]
                <=> [$a->isPosVenda(), $a->funil->prioridade, $b->id])
            ->first();

        if ($proxima) {
            $this->iniciar($proxima);
        }
    }

    /**
     * SAIR / PARAR: out of every funnel, for good.
     */
    public function descadastrar(FunilContato $contato): void
    {
        $contato->update(['descadastrado_em' => now()]);

        $contato->inscricoes()
            ->whereIn('status', FunilInscricaoStatus::abertos())
            ->update([
                'status' => FunilInscricaoStatus::Descadastrado,
                'proximo_envio_em' => null,
                'finalizado_em' => now(),
            ]);
    }

    /**
     * A deactivated funnel stops sending: its contacts wait and their other funnels go on.
     */
    public function pausarFunil(Funil $funil): void
    {
        $inscricoes = $funil->inscricoes()->where('status', FunilInscricaoStatus::NoFunil)->with('contato')->get();

        foreach ($inscricoes as $inscricao) {
            $inscricao->update(['status' => FunilInscricaoStatus::EmEspera, 'proximo_envio_em' => null]);
            $this->ativarProxima($inscricao->contato);
        }
    }

    /**
     * A reactivated funnel gets back the contacts that have nothing else sending.
     */
    public function retomarFunil(Funil $funil): void
    {
        $contatos = $funil->inscricoes()->where('status', FunilInscricaoStatus::EmEspera)->with('contato')->get()->pluck('contato');

        foreach ($contatos as $contato) {
            $this->ativarProxima($contato);
        }
    }

    private function iniciar(FunilInscricao $inscricao): void
    {
        // Paused in the middle of a block: continue now.
        if ($inscricao->bloco_atual_id !== null) {
            $inscricao->update(['status' => FunilInscricaoStatus::NoFunil, 'proximo_envio_em' => now()]);

            return;
        }

        $bloco = $inscricao->funil->primeiroBloco();

        if (! $bloco) {
            $this->concluir($inscricao);

            return;
        }

        $inscricao->update([
            'status' => FunilInscricaoStatus::NoFunil,
            'bloco_atual_id' => $bloco->id,
            'ultima_ordem_enviada' => 0,
            'proximo_envio_em' => now()->addMinutes($bloco->espera_inicio_min + $bloco->esperaPrimeiraMensagem()),
        ]);
    }
}
