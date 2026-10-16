<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WebhookCall;
use App\Services\Hotmart\HotmartEventProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class HotmartWebhookController extends Controller
{
    /**
     * Hotmart purchases can't be re-checked through a simple API call, so the
     * hottok is mandatory: without it every notification is refused.
     */
    public function __invoke(Request $request, HotmartEventProcessor $processor): Response
    {
        $payload = $request->except('hottok');

        $call = WebhookCall::query()->create([
            'gateway' => PaymentGateway::Hotmart,
            'event' => $request->input('event'),
            'external_id' => $request->input('data.purchase.transaction'),
            'payload' => $payload,
        ]);

        $expected = (string) Setting::value('hotmart_hottok', config('services.hotmart.hottok'));
        $received = (string) ($request->header('X-HOTMART-HOTTOK') ?? $request->input('hottok'));

        if ($expected === '' || ! hash_equals($expected, $received)) {
            $call->markAs(WebhookCall::RESULT_FAILED, $expected === '' ? 'Hottok da Hotmart não configurado (Admin > Configurações).' : 'Hottok inválido.');

            return response('unauthorized', 401);
        }

        try {
            $result = $processor->process($payload);
        } catch (Throwable $exception) {
            report($exception);
            $call->markAs(WebhookCall::RESULT_FAILED, $exception->getMessage());

            return response('error', 500);
        }

        $call->markAs(
            $result['handled'] ? WebhookCall::RESULT_PROCESSED : WebhookCall::RESULT_IGNORED,
            $result['message'],
            $result['order'],
        );

        return response('ok');
    }
}
