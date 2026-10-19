<?php

namespace App\Services\Funil;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sends WhatsApp messages through the Evolution API (v2).
 */
class EvolutionApi
{
    /**
     * Milliseconds Evolution shows "typing..." before the message.
     */
    private const DELAY_MS = 3000;

    public function enviarTexto(string $instancia, string $telefone, string $texto): void
    {
        $url = rtrim((string) Setting::value('evolution_url', config('services.evolution.url')), '/');
        $apiKey = (string) Setting::value('evolution_api_key', config('services.evolution.api_key'));

        if ($url === '' || $apiKey === '') {
            throw new EvolutionException('Evolution API não configurada (Admin > Configurações).');
        }

        try {
            $response = Http::withHeaders(['apikey' => $apiKey])
                ->acceptJson()
                ->timeout(30)
                ->post($url.'/message/sendText/'.rawurlencode($instancia), [
                    'number' => $telefone,
                    'text' => $texto,
                    'delay' => self::DELAY_MS,
                ]);
        } catch (ConnectionException $exception) {
            throw new EvolutionException('Sem conexão com a Evolution API: '.$exception->getMessage());
        }

        if ($response->successful()) {
            return;
        }

        $detalhe = 'Evolution respondeu '.$response->status().': '.Str::limit($response->body(), 200);

        // 400: the number is not on WhatsApp. 401/404 (instance)/5xx: our side.
        throw new EvolutionException($detalhe, doContato: $response->status() === 400);
    }
}
