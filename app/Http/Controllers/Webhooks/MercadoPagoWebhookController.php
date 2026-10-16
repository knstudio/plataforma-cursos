<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\WebhookCall;
use App\Services\MercadoPago\MercadoPagoPaymentSync;
use App\Services\MercadoPago\MercadoPagoSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Handles both the Webhooks format (type=payment, data.id) and the legacy
     * IPN format (topic=payment, id). Answering 5xx makes Mercado Pago retry.
     */
    public function __invoke(Request $request, MercadoPagoSignature $signature, MercadoPagoPaymentSync $sync): Response
    {
        $type = $request->input('type') ?? $request->query('type') ?? $request->input('topic') ?? $request->query('topic');
        $paymentId = $request->input('data.id') ?? $request->query('data_id') ?? ($type === 'payment' ? $request->query('id') : null);

        $call = WebhookCall::query()->create([
            'gateway' => PaymentGateway::MercadoPago,
            'event' => $request->input('action') ?? $type,
            'external_id' => $paymentId !== null ? (string) $paymentId : null,
            'payload' => $request->all(),
        ]);

        if (! $signature->isValid($request)) {
            $call->markAs(WebhookCall::RESULT_FAILED, 'Assinatura x-signature inválida.');

            return response('invalid signature', 401);
        }

        if ($type !== 'payment' || blank($paymentId)) {
            $call->markAs(WebhookCall::RESULT_IGNORED, 'Notificação que não é de pagamento.');

            return response('ignored');
        }

        try {
            $order = $sync->sync((string) $paymentId);
        } catch (Throwable $exception) {
            report($exception);
            $call->markAs(WebhookCall::RESULT_FAILED, $exception->getMessage());

            return response('error', 500);
        }

        if (! $order) {
            $call->markAs(WebhookCall::RESULT_IGNORED, 'Pagamento não pertence a um pedido desta plataforma.');

            return response('ignored');
        }

        $call->markAs(WebhookCall::RESULT_PROCESSED, 'Status: '.$order->status->label(), $order);

        return response('ok');
    }
}
