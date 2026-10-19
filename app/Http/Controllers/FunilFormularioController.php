<?php

namespace App\Http\Controllers;

use App\Models\Funil;
use App\Services\Funil\FunilRecepcao;
use App\Services\Funil\Telefone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Landing page form (WordPress) of a funnel with a "formulario" entry. Fields:
 * funil (slug), nome, whatsApp, email (optional).
 */
class FunilFormularioController extends Controller
{
    public function __invoke(Request $request, FunilRecepcao $recepcao): RedirectResponse
    {
        $funil = Funil::query()->where('slug', (string) $request->input('funil'))->where('ativo', true)->first();
        $gatilho = $funil?->gatilhoFormulario();

        abort_unless($gatilho, 404);

        $gatilho->setRelation('funil', $funil);

        $telefone = Telefone::normalizar($request->input('whatsApp', $request->input('telefone')));
        $nome = Str::limit(Str::squish((string) $request->input('nome')), 100, '');
        $email = Str::lower(trim((string) $request->input('email'))) ?: null;

        if (! $telefone || $nome === '') {
            return redirect()->away($this->paginaDeErro($request, (string) $gatilho->opcao('pagina_obrigado')));
        }

        $recepcao->formulario($gatilho, $telefone, $nome, filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null);

        return redirect()->away((string) $gatilho->opcao('pagina_obrigado'));
    }

    /**
     * Back to the landing with ?erro=telefone (the landing shows the alert).
     */
    private function paginaDeErro(Request $request, string $fallback): string
    {
        $landing = (string) $request->headers->get('referer');

        if (! filter_var($landing, FILTER_VALIDATE_URL)) {
            return $fallback;
        }

        $landing = Str::before($landing, '#');

        return str_contains($landing, 'erro=telefone')
            ? $landing
            : $landing.(str_contains($landing, '?') ? '&' : '?').'erro=telefone';
    }
}
