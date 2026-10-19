<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FunilInscricaoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FunilBlocoRequest;
use App\Http\Requests\Admin\ReorderFunilRequest;
use App\Models\Funil;
use App\Models\FunilBloco;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FunilBlocoController extends Controller
{
    /**
     * New blocks go to the end of the funnel, starting one day after the previous one.
     */
    public function store(Request $request, Funil $funil): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'exists:products,id']], [], ['product_id' => 'produto']);

        $primeiro = ! $funil->blocos()->exists();

        $bloco = $funil->blocos()->create([
            'product_id' => $data['product_id'],
            'posicao' => (int) $funil->blocos()->max('posicao') + 1,
            'espera_inicio_min' => $primeiro ? 0 : 1440,
            'espera_se_comprou_min' => $primeiro ? null : 30,
            'ativo' => true,
        ]);

        return $this->backTo($bloco, 'Bloco criado no fim do funil.');
    }

    /**
     * Deactivating a block releases the contacts in it to the next block.
     */
    public function update(FunilBlocoRequest $request, FunilBloco $bloco): RedirectResponse
    {
        $bloco->update($request->attributesForSave());

        if (! $bloco->ativo) {
            $bloco->inscricoes()->where('status', FunilInscricaoStatus::NoFunil)->update(['proximo_envio_em' => now()]);
        }

        return $this->backTo($bloco, $bloco->ativo ? 'Bloco salvo.' : 'Bloco desativado. Quem estava nele passa para o próximo bloco.');
    }

    public function move(Request $request, FunilBloco $bloco): RedirectResponse
    {
        $direcao = $request->validate(['direcao' => ['required', Rule::in(['cima', 'baixo'])]])['direcao'];

        $ids = $bloco->funil->blocos()->pluck('id')->all();
        $atual = array_search($bloco->id, $ids, true);
        $destino = $direcao === 'cima' ? $atual - 1 : $atual + 1;

        if (isset($ids[$destino])) {
            [$ids[$atual], $ids[$destino]] = [$ids[$destino], $ids[$atual]];
            $this->renumerar($ids);
        }

        return $this->backTo($bloco, 'Ordem dos blocos salva.');
    }

    /**
     * Order coming from the drag-and-drop panel.
     */
    public function reorder(ReorderFunilRequest $request, Funil $funil): JsonResponse
    {
        $ids = $request->ids();
        $doFunil = $funil->blocos()->pluck('id')->all();

        if (collect($ids)->sort()->values()->all() !== collect($doFunil)->sort()->values()->all()) {
            return response()->json(['message' => 'A lista enviada não confere com o funil. Recarregue a página e tente de novo.'], 422);
        }

        $this->renumerar($ids);

        return response()->json(['message' => 'Ordem dos blocos salva.']);
    }

    /**
     * Only a block nobody is in can be removed; otherwise deactivate it.
     */
    public function destroy(FunilBloco $bloco): RedirectResponse
    {
        if ($bloco->inscricoes()->whereIn('status', FunilInscricaoStatus::abertos())->exists()) {
            return $this->backTo($bloco, null)->with('error', 'Há contatos neste bloco. Desative-o: eles passam para o próximo bloco.');
        }

        $funil = $bloco->funil;
        $bloco->delete();
        $this->renumerar($funil->blocos()->pluck('id')->all());

        return redirect()->to(route('admin.funis.edit', $funil).'#blocos')->with('status', 'Bloco excluído.');
    }

    /**
     * @param  list<int>  $ids
     */
    private function renumerar(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            foreach (array_values($ids) as $index => $id) {
                FunilBloco::query()->whereKey($id)->update(['posicao' => $index + 1]);
            }
        });
    }

    private function backTo(FunilBloco $bloco, ?string $status): RedirectResponse
    {
        $redirect = redirect()->to(route('admin.funis.edit', $bloco->funil_id).'#bloco-'.$bloco->id);

        return $status ? $redirect->with('status', $status) : $redirect;
    }
}
