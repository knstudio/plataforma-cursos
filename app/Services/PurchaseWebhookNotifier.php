<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells external tools (GoatFlow post-sale flows) that an order was approved.
 * Contract: C:\laragon\www\integracao-juliana\CONTRATO.md, section 2.2.
 */
class PurchaseWebhookNotifier
{
    private const TIMEOUT_SECONDS = 10;

    /**
     * One POST per distinct URL of the order's products (main product first): GoatFlow
     * restarts a flow on every new POST for the same phone, so the same URL never gets two.
     */
    public function notify(Order $order): void
    {
        $order->loadMissing(['user', 'product', 'items.product']);

        $telefone = PhoneNumber::whatsapp($order->user?->phone);

        foreach ($this->itemsByUrl($order) as $url => $item) {
            if (! $telefone) {
                Log::warning('Aviso de compra não enviado: comprador sem telefone válido.', ['order' => $order->id, 'url' => $url]);

                continue;
            }

            $this->send($url, [
                'telefone' => $telefone,
                'nome' => $order->user->name,
                'email' => $order->user->email,
                'produto' => $item['product']->slug,
                'produto_nome' => $item['product']->name,
                'valor' => number_format((float) $item['price'], 2, ',', '.'),
                'pedido' => $order->id,
            ], $order);
        }
    }

    /**
     * @return array<string, array{product: Product, price: string|float}>
     */
    private function itemsByUrl(Order $order): array
    {
        $items = $order->items->isNotEmpty()
            ? $order->items->sortBy(fn (OrderItem $item) => $item->is_order_bump)->map(fn (OrderItem $item) => ['product' => $item->product, 'price' => $item->unit_price])
            : collect([['product' => $order->product, 'price' => $order->amount]]);

        $byUrl = [];

        foreach ($items as $item) {
            $url = $item['product']?->purchase_webhook_url;

            if (filled($url) && ! isset($byUrl[$url])) {
                $byUrl[$url] = $item;
            }
        }

        return $byUrl;
    }

    /**
     * Failures are only logged: the purchase is already approved and the access granted.
     *
     * @param  array<string, mixed>  $payload
     */
    private function send(string $url, array $payload, Order $order): void
    {
        try {
            $response = Http::acceptJson()->asJson()->timeout(self::TIMEOUT_SECONDS)->post($url, $payload);

            if ($response->failed()) {
                Log::warning('Aviso de compra recusado.', ['order' => $order->id, 'url' => $url, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            }
        } catch (Throwable $exception) {
            Log::warning('Aviso de compra falhou.', ['order' => $order->id, 'url' => $url, 'error' => $exception->getMessage()]);
        }
    }
}
