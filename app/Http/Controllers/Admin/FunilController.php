<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FunilGatilhoTipo;
use App\Enums\FunilInscricaoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FunilRequest;
use App\Models\Funil;
use App\Models\Product;
use App\Services\Funil\FunilAgenda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FunilController extends Controller
{
    public function index(): View
    {
        $status = fn (FunilInscricaoStatus $status) => fn (Builder $inscricoes) => $inscricoes->where('status', $status);

        $funis = Funil::query()
            ->with('gatilhos.product:id,name')
            ->withCount([
                'blocos',
                'inscricoes as aguardando_count' => $status(FunilInscricaoStatus::AguardandoConfirmacao),
                'inscricoes as no_funil_count' => $status(FunilInscricaoStatus::NoFunil),
                'inscricoes as em_espera_count' => $status(FunilInscricaoStatus::EmEspera),
                'inscricoes as concluido_count' => $status(FunilInscricaoStatus::Concluido),
            ])
            ->orderByDesc('ativo')
            ->orderByDesc('prioridade')
            ->orderBy('nome')
            ->get();

        return view('admin.funis.index', [
            'funis' => $funis,
        ]);
    }

    public function create(): View
    {
        return view('admin.funis.create', [
            'funil' => new Funil(['prioridade' => 0, 'hora_inicio_envio' => 8, 'hora_fim_envio' => 21, 'ativo' => true]),
        ]);
    }

    public function store(FunilRequest $request): RedirectResponse
    {
        $funil = Funil::query()->create($request->validated());

        return redirect()->route('admin.funis.edit', $funil)
            ->with('status', 'Funil criado. Agora cadastre a entrada (gatilho) e os blocos.');
    }

    /**
     * The funnel panel: entry triggers, blocks and messages, contacts.
     */
    public function edit(Funil $funil): View
    {
        $funil->load([
            'gatilhos.product:id,name',
            'blocos.product.offers',
            'blocos.mensagens.offer',
        ]);

        return view('admin.funis.edit', [
            'funil' => $funil,
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'slug', 'price', 'is_active']),
            'tiposGatilho' => FunilGatilhoTipo::cases(),
            'contatosPorBloco' => $funil->inscricoes()
                ->where('status', FunilInscricaoStatus::NoFunil)
                ->selectRaw('bloco_atual_id, count(*) as total')
                ->groupBy('bloco_atual_id')
                ->pluck('total', 'bloco_atual_id'),
            'resumo' => $funil->inscricoes()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'inscricoes' => $funil->inscricoes()
                ->with(['contato', 'blocoAtual.product:id,name', 'gatilho'])
                ->latest('id')
                ->limit(50)
                ->get(),
        ]);
    }

    public function update(FunilRequest $request, Funil $funil, FunilAgenda $agenda): RedirectResponse
    {
        $estavaAtivo = $funil->ativo;

        $funil->update($request->validated());

        if ($estavaAtivo && ! $funil->ativo) {
            $agenda->pausarFunil($funil);
        } elseif (! $estavaAtivo && $funil->ativo) {
            $agenda->retomarFunil($funil);
        }

        return redirect()->to(route('admin.funis.edit', $funil).'#configuracoes')->with('status', 'Funil atualizado.');
    }

    /**
     * Funnels that already had contacts keep the history; deactivate them instead.
     */
    public function destroy(Funil $funil): RedirectResponse
    {
        if ($funil->inscricoes()->exists()) {
            return back()->with('error', 'Este funil já tem contatos. Desative-o em vez de excluir.');
        }

        $funil->delete();

        return redirect()->route('admin.funis.index')->with('status', 'Funil excluído.');
    }
}
