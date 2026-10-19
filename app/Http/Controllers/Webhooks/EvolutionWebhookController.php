<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Funil\FunilRecepcao;
use App\Services\Funil\Telefone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Throwable;

/**
 * Evolution API webhook (event MESSAGES_UPSERT). Evolution can't sign its calls,
 * so the URL carries a token generated in Admin > Configurações.
 */
class EvolutionWebhookController extends Controller
{
    public function __invoke(Request $request, FunilRecepcao $recepcao): Response
    {
        $expected = (string) Setting::value('evolution_webhook_token');

        if ($expected === '' || ! hash_equals($expected, (string) $request->query('token'))) {
            return response('unauthorized', 401);
        }

        $key = (array) $request->input('data.key', []);
        $event = Str::lower(str_replace('_', '.', (string) $request->input('event')));

        if ($event !== 'messages.upsert' || ($key['fromMe'] ?? false) === true || str_ends_with((string) ($key['remoteJid'] ?? ''), '@g.us')) {
            return response('ignored');
        }

        // remoteJid may come as @lid; the real number is in one of the alternatives.
        $jid = collect([$key['remoteJid'] ?? null, $key['remoteJidAlt'] ?? null, $key['senderPn'] ?? null])
            ->first(fn ($candidate) => str_ends_with((string) $candidate, '@s.whatsapp.net'));

        $telefone = Telefone::normalizar(Str::before((string) $jid, '@'));
        $messageId = (string) ($key['id'] ?? '');

        if (! $telefone || $messageId === '') {
            return response('ignored');
        }

        $texto = (string) ($request->input('data.message.conversation') ?? $request->input('data.message.extendedTextMessage.text') ?? '');

        try {
            $recepcao->mensagemRecebida(
                (string) $request->input('instance'),
                $telefone,
                $texto,
                $messageId,
                Str::limit(trim((string) $request->input('data.pushName')), 100, '') ?: null,
            );
        } catch (Throwable $exception) {
            // The message id is already recorded: a retry from Evolution would be ignored anyway.
            report($exception);
        }

        return response('ok');
    }
}
