<?php

namespace App\Services\MercadoPago;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Builds the Checkout Pro preference for an order. Prices always come from
 * the order items (saved from the database), never from the browser.
 */
class MercadoPagoCheckout
{
    public function __construct(private MercadoPagoClient $client) {}

    /**
     * Create the preference and return the URL the buyer must be sent to.
     */
    public function start(Order $order): string
    {
        $order->loadMissing(['items', 'user']);

        $returnUrl = URL::signedRoute('checkout.result', ['order' => $order]);

        $preference = array_filter([
            'items' => $order->items->map(fn (OrderItem $item) => [
                'id' => (string) $item->product_id,
                'title' => Str::limit($item->description, 250, ''),
                'quantity' => 1,
                'unit_price' => (float) $item->unit_price,
                'currency_id' => 'BRL',
            ])->values()->all(),
            'payer' => [
                'name' => $order->user->name,
                'email' => $order->user->email,
            ],
            'external_reference' => (string) $order->id,
            'back_urls' => [
                'success' => $returnUrl,
                'pending' => $returnUrl,
                'failure' => $returnUrl,
            ],
            // Mercado Pago refuses auto_return for non-public (local) return URLs.
            'auto_return' => str_starts_with($returnUrl, 'https://') ? 'approved' : null,
            'notification_url' => $this->notificationUrl(),
            'statement_descriptor' => config('services.mercadopago.statement_descriptor'),
            'metadata' => ['order_id' => $order->id],
        ], fn ($value) => $value !== null);

        $response = $this->client->createPreference($preference);

        $order->update(['preference_id' => $response['id'] ?? null]);

        return $response['init_point'] ?? throw new MercadoPagoException('Preferência criada sem init_point.');
    }

    /**
     * Mercado Pago only calls public HTTPS URLs, so local environments skip it
     * (the return page syncs the payment instead).
     */
    private function notificationUrl(): ?string
    {
        $url = config('services.mercadopago.notification_url') ?: route('webhooks.mercadopago');

        if (! str_starts_with($url, 'https://')) {
            return null;
        }

        // Without it, Mercado Pago also sends the legacy IPN format (topic=payment and
        // topic=merchant_order) for the same payment, multiplying the notifications.
        return $url.(str_contains($url, '?') ? '&' : '?').'source_news=webhooks';
    }
}
