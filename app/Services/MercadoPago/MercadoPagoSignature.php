<?php

namespace App\Services\MercadoPago;

use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Validates the x-signature header Mercado Pago sends with webhooks.
 *
 * @see https://www.mercadopago.com.br/developers/pt/docs/your-integrations/notifications/webhooks
 */
class MercadoPagoSignature
{
    /**
     * Without a configured secret there is nothing to compare against; the
     * payment is still re-read from the API, so a forged call cannot approve anything.
     */
    public function isValid(Request $request): bool
    {
        $secret = Setting::value('mercadopago_webhook_secret', config('services.mercadopago.webhook_secret'));

        if (blank($secret)) {
            return true;
        }

        $parts = collect(explode(',', (string) $request->header('x-signature')))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

                return [$key => $value];
            });

        $timestamp = $parts->get('ts');
        $hash = $parts->get('v1');

        if (blank($timestamp) || blank($hash)) {
            return false;
        }

        // PHP turns the "data.id" query parameter into "data_id".
        $dataId = (string) $request->query('data_id', (string) $request->input('data.id'));

        if (ctype_alnum($dataId)) {
            $dataId = strtolower($dataId);
        }

        $manifest = '';

        if ($dataId !== '') {
            $manifest .= "id:{$dataId};";
        }

        if ($request->hasHeader('x-request-id')) {
            $manifest .= 'request-id:'.$request->header('x-request-id').';';
        }

        $manifest .= "ts:{$timestamp};";

        return hash_equals(hash_hmac('sha256', $manifest, $secret), (string) $hash);
    }
}
