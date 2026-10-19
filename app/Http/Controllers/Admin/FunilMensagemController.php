<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FunilMensagemRequest;
use App\Http\Requests\Admin\ReorderFunilRequest;
use App\Models\FunilBloco;
use App\Models\FunilMensagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FunilMensagemController extends Controller
{
    private const TEXTO_INICIAL = "Oi, {primeiro_nome}! Conheça o {produto} por R$ {preco}\n\n👉 {link}";

    /**
     * New message at the end of the block, with the product's first active offer.
     */
    public function store(FunilBloco $bloco): RedirectResponse
    {
        $ultimaOrdem = (int) $bloco->mensagens()->max('ordem');

        $bloco->mensagens()->create([
            'ordem' => $ultimaOrdem + 1,
            'espera_seg' => $ultimaOrdem > 0 ? 86400 : 0,
            'product_offer_id' => $bloco->product?->offers()->where('is_active', true)->value('id'),
            'texto' => self::TEXTO_INICIAL,
            'ativo' => true,
        ]);

        return $this->backTo($bloco, 'Mensagem criada.');
    }

    public function update(FunilMensagemRequest $request, FunilMensagem $mensagem): RedirectResponse
    {
        $mensagem->update($request->attributesForSave());

        return $this->backTo($mensagem->bloco, 'Mensagem salva.');
    }

    public function move(Request $request, FunilMensagem $mensagem): RedirectResponse
    {
        $direcao = $request->validate(['direcao' => ['required', Rule::in(['cima', 'baixo'])]])['direcao'];

        $ids = $mensagem->bloco->mensagens()->pluck('id')->all();
        $atual = array_search($mensagem->id, $ids, true);
        $destino = $direcao === 'cima' ? $atual - 1 : $atual + 1;

        if (isset($ids[$destino])) {
            [$ids[$atual], $ids[$destino]] = [$ids[$destino], $ids[$atual]];
            $this->renumerar($ids);
        }

        return $this->backTo($mensagem->bloco, 'Ordem das mensagens salva.');
    }

    /**
     * Order coming from the drag-and-drop panel.
     */
    public function reorder(ReorderFunilRequest $request, FunilBloco $bloco): JsonResponse
    {
        $ids = $request->ids();
        $doBloco = $bloco->mensagens()->pluck('id')->all();

        if (collect($ids)->sort()->values()->all() !== collect($doBloco)->sort()->values()->all()) {
            return response()->json(['message' => 'A lista enviada não confere com o bloco. Recarregue a página e tente de novo.'], 422);
        }

        $this->renumerar($ids);

        return response()->json(['message' => 'Ordem das mensagens salva.']);
    }

    public function destroy(FunilMensagem $mensagem): RedirectResponse
    {
        $bloco = $mensagem->bloco;
        $mensagem->delete();

        return $this->backTo($bloco, 'Mensagem excluída.');
    }

    /**
     * @param  list<int>  $ids
     */
    private function renumerar(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            foreach (array_values($ids) as $index => $id) {
                FunilMensagem::query()->whereKey($id)->update(['ordem' => $index + 1]);
            }
        });
    }

    private function backTo(FunilBloco $bloco, string $status): RedirectResponse
    {
        return redirect()->to(route('admin.funis.edit', $bloco->funil_id).'#bloco-'.$bloco->id)->with('status', $status);
    }
}
