<?php

namespace App\Services\MercadoPago;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Thin wrapper over the two Mercado Pago endpoints the platform uses.
 */
class MercadoPagoClient
{
    /**
     * Create a Checkout Pro preference.
     *
     * @param  array<string, mixed>  $preference
     * @return array<string, mixed>
     */
    public function createPreference(array $preference): array
    {
        return $this->send(fn (PendingRequest $http) => $http
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post('/checkout/preferences', $preference));
    }

    /**
     * Fetch a payment. Notifications only carry the ID, so this is the source of truth.
     *
     * @return array<string, mixed>
     */
    public function payment(string $paymentId): array
    {
        return $this->send(fn (PendingRequest $http) => $http->get('/v1/payments/'.rawurlencode($paymentId)));
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     */
    private function send(callable $call): array
    {
        $token = Setting::value('mercadopago_access_token', config('services.mercadopago.access_token'));

        if (blank($token)) {
            throw new MercadoPagoException('Access Token do Mercado Pago não configurado (Admin > Configurações).');
        }

        $http = Http::baseUrl(config('services.mercadopago.base_url'))
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(20);

        try {
            return $call($http)->throw()->json();
        } catch (RequestException $exception) {
            throw new MercadoPagoException(
                'Mercado Pago respondeu '.$exception->response->status().': '.$exception->response->body(),
                previous: $exception,
            );
        } catch (ConnectionException $exception) {
            throw new MercadoPagoException('Não foi possível conectar ao Mercado Pago.', previous: $exception);
        }
    }
}
