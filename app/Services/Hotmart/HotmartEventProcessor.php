<?php

namespace App\Services\Hotmart;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Models\Order;
use App\Models\Product;
use App\Services\BuyerAccount;
use App\Services\OrderFulfillment;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Applies a Hotmart webhook (version 2.0.0) to the platform. Each Hotmart
 * transaction becomes one order; order bumps arrive as separate transactions.
 */
class HotmartEventProcessor
{
    private const APPROVAL_EVENTS = ['PURCHASE_APPROVED', 'PURCHASE_COMPLETE'];

    private const REFUND_EVENTS = ['PURCHASE_REFUNDED'];

    private const CHARGEBACK_EVENTS = ['PURCHASE_CHARGEBACK'];

    private const CANCEL_EVENTS = ['PURCHASE_CANCELED', 'PURCHASE_EXPIRED'];

    public function __construct(
        private BuyerAccount $buyers,
        private OrderFulfillment $fulfillment,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{handled: bool, message: string, order: Order|null}
     */
    public function process(array $payload): array
    {
        $event = (string) Arr::get($payload, 'event');

        if (! in_array($event, [...self::APPROVAL_EVENTS, ...self::REFUND_EVENTS, ...self::CHARGEBACK_EVENTS, ...self::CANCEL_EVENTS], true)) {
            return ['handled' => false, 'message' => "Evento {$event} não utilizado.", 'order' => null];
        }

        $hotmartProductId = (string) Arr::get($payload, 'data.product.id');
        $transaction = (string) Arr::get($payload, 'data.purchase.transaction');
        $email = (string) Arr::get($payload, 'data.buyer.email');

        if ($transaction === '' || $email === '') {
            return ['handled' => false, 'message' => 'Payload sem transação ou e-mail do comprador.', 'order' => null];
        }

        $product = Product::query()->where('hotmart_product_id', $hotmartProductId)->first();

        if (! $product) {
            return ['handled' => false, 'message' => "Produto Hotmart {$hotmartProductId} não está vinculado a nenhum produto.", 'order' => null];
        }

        $order = $this->findOrCreateOrder($payload, $product, $transaction, $email);

        match (true) {
            in_array($event, self::APPROVAL_EVENTS, true) => $this->fulfillment->approve($order, $this->approvedAt($payload)),
            in_array($event, self::REFUND_EVENTS, true) => $this->fulfillment->reverse($order, OrderStatus::Refunded),
            in_array($event, self::CHARGEBACK_EVENTS, true) => $this->fulfillment->reverse($order, OrderStatus::Chargeback),
            default => $this->fulfillment->cancel($order),
        };

        return ['handled' => true, 'message' => "{$event} aplicado.", 'order' => $order->refresh()];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function findOrCreateOrder(array $payload, Product $product, string $transaction, string $email): Order
    {
        $order = Order::query()
            ->where('gateway', PaymentGateway::Hotmart)
            ->where('external_id', $transaction)
            ->first();

        if ($order) {
            $order->update(['payload' => $payload]);

            return $order;
        }

        $user = $this->buyers->resolve(
            $email,
            (string) Arr::get($payload, 'data.buyer.name', ''),
            Arr::get($payload, 'data.buyer.checkout_phone'),
        );

        $amount = (float) (Arr::get($payload, 'data.purchase.price.value') ?? Arr::get($payload, 'data.purchase.full_price.value') ?? $product->price);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'gateway' => PaymentGateway::Hotmart,
            'external_id' => $transaction,
            'payment_method' => Arr::get($payload, 'data.purchase.payment.type'),
            'status' => OrderStatus::Pending,
            'amount' => $amount,
            'payload' => $payload,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'description' => $product->name,
            'unit_price' => $amount,
        ]);

        return $order;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function approvedAt(array $payload): Carbon
    {
        $milliseconds = Arr::get($payload, 'data.purchase.approved_date');

        return $milliseconds ? Carbon::createFromTimestampMs($milliseconds) : now();
    }
}
