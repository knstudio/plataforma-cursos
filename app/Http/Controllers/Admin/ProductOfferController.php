<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductOfferRequest;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductOfferController extends Controller
{
    public function create(Product $product): View
    {
        return view('admin.offers.create', [
            'product' => $product->load('orderBumps.bumpProduct'),
            'offer' => new ProductOffer(['is_active' => true]),
        ]);
    }

    public function store(ProductOfferRequest $request, Product $product): RedirectResponse
    {
        $offer = DB::transaction(function () use ($request, $product): ProductOffer {
            $offer = $product->offers()->create($this->attributes($request));
            $this->syncBumpOverrides($offer, $request->validated('bumps', []));

            return $offer;
        });

        return redirect()->to(route('admin.products.edit', $product).'#ofertas')
            ->with('status', 'Oferta criada. Link: '.$offer->checkoutUrl());
    }

    public function edit(ProductOffer $offer): View
    {
        return view('admin.offers.edit', [
            'offer' => $offer->load(['product.orderBumps.bumpProduct', 'bumpOverrides']),
        ]);
    }

    public function update(ProductOfferRequest $request, ProductOffer $offer): RedirectResponse
    {
        DB::transaction(function () use ($request, $offer): void {
            $offer->update($this->attributes($request));
            $this->syncBumpOverrides($offer, $request->validated('bumps', []));
        });

        return redirect()->to(route('admin.products.edit', $offer->product).'#ofertas')
            ->with('status', 'Oferta atualizada.');
    }

    /**
     * Offers already used in orders are kept for the campaign report; they are deactivated instead.
     */
    public function destroy(ProductOffer $offer): RedirectResponse
    {
        $product = $offer->product;

        if ($offer->orders()->exists()) {
            $offer->update(['is_active' => false]);

            return redirect()->to(route('admin.products.edit', $product).'#ofertas')
                ->with('status', 'A oferta tem pedidos, então foi apenas desativada (o histórico continua no relatório).');
        }

        $offer->delete();

        return redirect()->to(route('admin.products.edit', $product).'#ofertas')->with('status', 'Oferta excluída.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ProductOfferRequest $request): array
    {
        $attributes = $request->safe()->except('bumps');

        $attributes['expires_at'] = $attributes['expires_at']
            ? Carbon::parse($attributes['expires_at'])->endOfDay()
            : null;

        return $attributes;
    }

    /**
     * Saves only what differs from the product: a price of its own or the bump removed.
     *
     * @param  array<int|string, array{price: string|null, removed: bool}>  $rows
     */
    private function syncBumpOverrides(ProductOffer $offer, array $rows): void
    {
        $offer->bumpOverrides()->delete();

        foreach ($offer->product->orderBumps()->pluck('id') as $bumpId) {
            $row = $rows[$bumpId] ?? null;

            if ($row && ($row['price'] !== null || $row['removed'])) {
                $offer->bumpOverrides()->create([
                    'order_bump_id' => $bumpId,
                    'price' => $row['price'],
                    'is_removed' => $row['removed'],
                ]);
            }
        }

        $offer->unsetRelation('bumpOverrides');
    }
}
