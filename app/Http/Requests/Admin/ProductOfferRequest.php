<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ParsesBrazilianDecimals;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductOfferRequest extends FormRequest
{
    use ParsesBrazilianDecimals;

    /**
     * Kept apart from the product form errors shown on the same page.
     *
     * @var string
     */
    protected $errorBag = 'offer';

    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ProductOffer|null $offer */
        $offer = $this->route('offer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:60', 'alpha_dash',
                Rule::unique('product_offers', 'code')->where('product_id', $this->product()->id)->ignore($offer?->id),
            ],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            // Keyed by order bump id: a different price and/or removal for this offer.
            'bumps' => ['array'],
            'bumps.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'bumps.*.removed' => ['boolean'],
            'without_order_bump' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome da oferta',
            'code' => 'código do link',
            'price' => 'preço',
            'bumps.*.price' => 'preço do order bump',
            'expires_at' => 'validade',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::slug($this->input('code') ?: $this->input('name', '')),
            'price' => $this->decimal($this->input('price')),
            'bumps' => collect((array) $this->input('bumps', []))
                ->filter(fn ($row) => is_array($row))
                ->map(fn (array $row) => [
                    'price' => $this->decimal($row['price'] ?? null),
                    'removed' => filter_var($row['removed'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ])
                ->all(),
            'without_order_bump' => $this->boolean('without_order_bump'),
            'is_active' => $this->boolean('is_active'),
            'expires_at' => filled($this->input('expires_at')) ? $this->input('expires_at') : null,
        ]);
    }

    private function product(): Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : $this->route('offer')->product;
    }
}
