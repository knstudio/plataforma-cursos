<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Http\Requests\CheckoutRequest;
use App\Models\FunilInscricao;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Services\BuyerAccount;
use App\Services\MercadoPago\MercadoPagoCheckout;
use App\Services\MercadoPago\MercadoPagoException;
use App\Services\MercadoPago\MercadoPagoPaymentSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Query parameters Mercado Pago appends to the return URL; they are not part
     * of the signed URL.
     */
    private const MERCADOPAGO_RETURN_PARAMS = [
        'collection_id', 'collection_status', 'payment_id', 'status', 'external_reference',
        'payment_type', 'merchant_order_id', 'preference_id', 'site_id', 'processing_mode',
        'merchant_account_id',
    ];

    /**
     * Product checkout, optionally with a campaign offer (special price link).
     */
    public function show(Request $request, Product $product, ?ProductOffer $offer = null): View|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        if ($offer && ! $offer->isAvailable()) {
            return $this->offerUnavailable($product, $request->query('fi'));
        }

        return view('checkout.show', [
            'product' => $product,
            'offer' => $offer,
            'bumps' => $product->checkoutBumps($offer),
            // WhatsApp funnel links carry ?fi=<enrollment>, kept until the order is created.
            'funilInscricaoId' => ctype_digit((string) $request->query('fi')) ? (int) $request->query('fi') : null,
        ]);
    }

    public function store(
        CheckoutRequest $request,
        BuyerAccount $buyers,
        MercadoPagoCheckout $checkout,
        Product $product,
        ?ProductOffer $offer = null,
    ): RedirectResponse {
        abort_unless($product->is_active, 404);

        if ($offer && ! $offer->isAvailable()) {
            return $this->offerUnavailable($product, $request->validated('fi'));
        }

        // Only bumps this checkout really shows can be bought, at the price it shows.
        $chosenBumps = $product->checkoutBumps($offer)
            ->whereIn('id', array_map('intval', $request->validated('order_bumps', [])));

        $order = DB::transaction(function () use ($request, $product, $offer, $buyers, $chosenBumps): Order {
            $user = $buyers->resolve($request->validated('email'), $request->validated('name'), $request->validated('phone'));

            $items = [[
                'product_id' => $product->id,
                'description' => $product->name,
                'unit_price' => $product->priceFor($offer),
                'is_order_bump' => false,
            ]];

            foreach ($chosenBumps as $bump) {
                $items[] = [
                    'product_id' => $bump->bumpProduct->id,
                    'description' => $bump->bumpProduct->name,
                    'unit_price' => $bump->priceFor($offer),
                    'is_order_bump' => true,
                ];
            }

            $order = Order::query()->create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'product_offer_id' => $offer?->id,
                'funil_inscricao_id' => FunilInscricao::query()->whereKey($request->validated('fi'))->value('id'),
                'gateway' => PaymentGateway::MercadoPago,
                'status' => OrderStatus::Pending,
                'amount' => collect($items)->sum(fn (array $item) => (float) $item['unit_price']),
            ]);

            $order->items()->createMany($items);

            return $order;
        });

        try {
            $paymentUrl = $checkout->start($order);
        } catch (MercadoPagoException $exception) {
            report($exception);
            $order->update(['status' => OrderStatus::Cancelled]);

            return back()->withInput()->with('error', 'Não conseguimos abrir o pagamento agora. Tente novamente em instantes.');
        }

        return redirect()->away($paymentUrl);
    }

    /**
     * Page the buyer lands on when coming back from Mercado Pago. It also syncs
     * the payment right away, so access doesn't depend on the webhook timing.
     */
    public function result(Request $request, Order $order, MercadoPagoPaymentSync $sync): View
    {
        abort_unless($request->hasValidSignatureWhileIgnoring(self::MERCADOPAGO_RETURN_PARAMS), 403);

        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');

        if (filled($paymentId) && $paymentId !== 'null' && $order->status !== OrderStatus::Approved) {
            try {
                $sync->sync((string) $paymentId);
                $order->refresh();
            } catch (MercadoPagoException $exception) {
                report($exception);
            }
        }

        return view('checkout.result', [
            'order' => $order->load(['items', 'user', 'offer']),
        ]);
    }

    /**
     * Expired or disabled campaign links fall back to the regular checkout.
     */
    private function offerUnavailable(Product $product, int|string|null $funilInscricaoId = null): RedirectResponse
    {
        return redirect()->route('checkout.show', array_filter(['product' => $product, 'fi' => $funilInscricaoId]))
            ->with('error', 'Esta oferta foi encerrada. Confira abaixo o valor atual.');
    }
}
