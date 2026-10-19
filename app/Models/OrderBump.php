<?php

namespace App\Models;

use Database\Factories\OrderBumpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;

/**
 * An extra product offered with a checkbox on another product's checkout.
 */
#[Fillable(['product_id', 'bump_product_id', 'price', 'headline', 'sort_order'])]
class OrderBump extends Model
{
    /** @use HasFactory<OrderBumpFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * The product whose checkout shows this bump.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The product the buyer gets when ticking the box.
     *
     * @return BelongsTo<Product, $this>
     */
    public function bumpProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bump_product_id');
    }

    /**
     * Whether the checkout shows it: the offered product must be on sale and
     * the campaign offer (if any) must not have removed it.
     */
    public function isAvailableFor(?ProductOffer $offer = null): bool
    {
        if ($offer?->without_order_bump || $offer?->bumpOverride($this)?->is_removed) {
            return false;
        }

        return $this->bumpProduct?->is_active === true;
    }

    /**
     * Price charged for the bump: the offer's own price when it sets one,
     * otherwise the special price set on the product (never the regular price).
     */
    public function priceFor(?ProductOffer $offer = null): string
    {
        return (string) ($offer?->bumpOverride($this)?->price ?? $this->price);
    }

    /**
     * Text next to the checkbox; text between asterisks is highlighted (*OPORTUNIDADE ÚNICA*).
     */
    public function headlineHtml(): HtmlString
    {
        $text = $this->headline ?: 'Sim! Quero aproveitar essa *OPORTUNIDADE ÚNICA* e incluir esta oferta no meu pedido.';

        return new HtmlString(preg_replace('/\*(.+?)\*/u', '<span class="highlight">$1</span>', e($text)));
    }
}
