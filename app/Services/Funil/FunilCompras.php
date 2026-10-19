<?php

namespace App\Services\Funil;

use App\Enums\FunilGatilhoTipo;
use App\Enums\FunilInscricaoStatus;
use App\Models\FunilContato;
use App\Models\FunilGatilho;
use App\Models\FunilInscricao;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * What an approved order does to the funnels (called by OrderFulfillment).
 * The purchase itself lives in orders; nothing is copied here.
 */
class FunilCompras
{
    public function __construct(private FunilAgenda $agenda) {}

    public function pedidoAprovado(Order $order): void
    {
        $order->loadMissing(['user', 'items']);

        /** @var Collection<int, int> $produtos */
        $produtos = $order->items->pluck('product_id')->push($order->product_id)->unique()->values();

        $gatilhos = FunilGatilho::query()
            ->where('tipo', FunilGatilhoTipo::CompraProduto)
            ->where('ativo', true)
            ->whereIn('product_id', $produtos)
            ->whereHas('funil', fn (Builder $funis) => $funis->where('ativo', true))
            ->orderBy('id')
            ->get();

        $contato = $this->contatoDoPedido($order, criar: $gatilhos->isNotEmpty());

        if (! $contato) {
            return;
        }

        // Bought the product of the block the contact is in: the dispatcher moves
        // on at its next run (and skips that product in every funnel).
        $contato->inscricoes()
            ->where('status', FunilInscricaoStatus::NoFunil)
            ->whereHas('blocoAtual', fn (Builder $blocos) => $blocos->whereIn('product_id', $produtos))
            ->update(['proximo_envio_em' => now()]);

        if ($contato->isDescadastrado()) {
            return;
        }

        foreach ($gatilhos->unique('funil_id') as $gatilho) {
            $inscricao = FunilInscricao::query()->firstOrCreate(
                ['contato_id' => $contato->id, 'funil_id' => $gatilho->funil_id],
                ['gatilho_id' => $gatilho->id, 'origem_order_id' => $order->id, 'status' => FunilInscricaoStatus::EmEspera],
            );

            if ($inscricao->wasRecentlyCreated) {
                $this->agenda->colocarNoFunil($inscricao, prioritaria: true);
            }
        }
    }

    /**
     * The contact behind the order: the enrollment of the funnel link, the one already
     * linked to the buyer, or the buyer's WhatsApp. Links the buyer account to it.
     */
    private function contatoDoPedido(Order $order, bool $criar): ?FunilContato
    {
        $user = $order->user;
        $telefone = Telefone::normalizar($user?->phone);

        $contato = $order->funilInscricao?->contato
            ?? FunilContato::query()->where('user_id', $order->user_id)->first()
            ?? ($telefone ? FunilContato::query()->where('telefone', $telefone)->first() : null);

        if (! $contato && $criar && $telefone) {
            $contato = FunilContato::query()->create([
                'telefone' => $telefone,
                'nome' => $user->name,
                'email' => $user->email,
                'user_id' => $user->id,
            ]);
        }

        if ($contato && $contato->user_id === null) {
            $contato->update(['user_id' => $order->user_id]);
        }

        return $contato;
    }
}
