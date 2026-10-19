<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FunilGatilhoRequest;
use App\Models\Funil;
use App\Models\FunilGatilho;
use Illuminate\Http\RedirectResponse;

class FunilGatilhoController extends Controller
{
    public function store(FunilGatilhoRequest $request, Funil $funil): RedirectResponse
    {
        $funil->gatilhos()->create($request->attributesForSave());

        return redirect()->to(route('admin.funis.edit', $funil).'#entrada')->with('status', 'Gatilho criado.');
    }

    public function update(FunilGatilhoRequest $request, FunilGatilho $gatilho): RedirectResponse
    {
        $gatilho->update($request->attributesForSave());

        return redirect()->to(route('admin.funis.edit', $gatilho->funil_id).'#entrada')->with('status', 'Gatilho atualizado.');
    }

    /**
     * Contacts that came through it stay in the funnel.
     */
    public function destroy(FunilGatilho $gatilho): RedirectResponse
    {
        $gatilho->delete();

        return redirect()->to(route('admin.funis.edit', $gatilho->funil_id).'#entrada')->with('status', 'Gatilho excluído.');
    }
}
