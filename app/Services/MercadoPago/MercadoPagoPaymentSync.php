<?php

namespace App\Services\MercadoPago;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Models\Order;
use App\Services\OrderFulfillment;
use Illuminate\Support\Carbon;

/**
 * Reads a payment from the Mercado Pago API and applies its status to the order
 * referenced by external_reference. Used by the webhook and the return page.
 */
class MercadoPagoPaymentSync
{
    public function __construct(
        private MercadoPagoClient $client,
        private OrderFulfillment $fulfillment,
    ) {}

    /**
     * @throws MercadoPagoException
     */
    public function sync(string $paymentId): ?Order
    {
        $payment = $this->client->payment($paymentId);

        $order = Order::query()
            ->where('gateway', PaymentGateway::MercadoPago)
            ->find((int) ($payment['external_reference'] ?? 0));

        if (! $order) {
            return null;
        }

        $status = (string) ($payment['status'] ?? '');
        $isCurrentPayment = $order->external_id === null || $order->external_id === (string) $payment['id'];

        // Once approved, notifications about other (failed) attempts must not overwrite the paid one.
        if ($order->status !== OrderStatus::Approved || $isCurrentPayment) {
            $order->update([
                'external_id' => (string) $payment['id'],
                'payment_method' => $payment['payment_method_id'] ?? $order->payment_method,
                'payload' => $payment,
            ]);
        }

        match ($status) {
            'approved' => $this->approve($order, $payment),
            'refunded' => $isCurrentPayment ? $this->fulfillment->reverse($order, OrderStatus::Refunded) : null,
            'charged_back' => $isCurrentPayment ? $this->fulfillment->reverse($order, OrderStatus::Chargeback) : null,
            'rejected', 'cancelled' => $this->fulfillment->cancel($order),
            default => $this->fulfillment->markPending($order),
        };

        return $order->refresh();
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function approve(Order $order, array $payment): void
    {
        $paid = (float) ($payment['transaction_amount'] ?? 0);

        if ($paid + 0.009 < (float) $order->amount) {
            throw new MercadoPagoException(
                "Pagamento {$payment['id']} aprovado com valor R$ {$paid}, menor que o pedido (R$ {$order->amount}).",
            );
        }

        $paidAt = isset($payment['date_approved']) ? Carbon::parse($payment['date_approved']) : now();

        $this->fulfillment->approve($order, $paidAt);
    }
}
