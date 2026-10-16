<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductOfferRequest;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ProductOfferController extends Controller
{
    public function create(Product $product): View
    {
        return view('admin.offers.create', [
            'product' => $product,
            'offer' => new ProductOffer(['is_active' => true]),
        ]);
    }

    public function store(ProductOfferRequest $request, Product $product): RedirectResponse
    {
        $offer = $product->offers()->create($this->attributes($request));

        return redirect()->to(route('admin.products.edit', $product).'#ofertas')
            ->with('status', 'Oferta criada. Link: '.$offer->checkoutUrl());
    }

    public function edit(ProductOffer $offer): View
    {
        return view('admin.offers.edit', [
            'offer' => $offer->load('product'),
        ]);
    }

    public function update(ProductOfferRequest $request, ProductOffer $offer): RedirectResponse
    {
        $offer->update($this->attributes($request));

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
        $attributes = $request->validated();

        $attributes['expires_at'] = $attributes['expires_at']
            ? Carbon::parse($attributes['expires_at'])->endOfDay()
            : null;

        return $attributes;
    }
}
