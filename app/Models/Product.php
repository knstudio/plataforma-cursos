<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

#[Fillable([
    'name', 'slug', 'description', 'price', 'access_days', 'hotmart_product_id', 'is_active',
    'order_bump_product_id', 'order_bump_price', 'order_bump_headline',
    'checkout_banner', 'checkout_timer_minutes', 'facebook_pixel_ids',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'access_days' => 'integer',
            'is_active' => 'boolean',
            'order_bump_price' => 'decimal:2',
            'checkout_timer_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Extra product offered with a checkbox on this product's checkout.
     *
     * @return BelongsTo<Product, $this>
     */
    public function orderBumpProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'order_bump_product_id');
    }

    /**
     * Special prices with their own checkout links.
     *
     * @return HasMany<ProductOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffer::class);
    }

    /**
     * Price charged on the checkout: the offer price when the buyer came through an offer link.
     */
    public function priceFor(?ProductOffer $offer = null): string
    {
        return (string) ($offer?->price ?? $this->price);
    }

    public function offersOrderBump(?ProductOffer $offer = null): bool
    {
        if ($offer?->without_order_bump) {
            return false;
        }

        return $this->order_bump_product_id !== null
            && $this->order_bump_price !== null
            && $this->orderBumpProduct?->is_active === true;
    }

    /**
     * Price charged for the bump: the special checkout price (or the offer's),
     * never the bump's regular price.
     */
    public function orderBumpPrice(?ProductOffer $offer = null): string
    {
        return (string) ($offer?->order_bump_price ?? $this->order_bump_price);
    }

    /**
     * Extra pixels fired only on this product's checkout (besides the global ones).
     *
     * @return list<string>
     */
    public function facebookPixelIds(): array
    {
        return Setting::parsePixelIds($this->facebook_pixel_ids);
    }

    public function checkoutBannerUrl(): string
    {
        return $this->checkout_banner
            ? Storage::disk('public')->url($this->checkout_banner)
            : asset(config('checkout.default_banner'));
    }

    /**
     * Order bump call-to-action; text between asterisks is highlighted (*OPORTUNIDADE ÚNICA*).
     */
    public function orderBumpHeadlineHtml(): HtmlString
    {
        $text = $this->order_bump_headline ?: 'Sim! Quero aproveitar essa *OPORTUNIDADE ÚNICA* e incluir esta oferta no meu pedido.';

        return new HtmlString(preg_replace('/\*(.+?)\*/u', '<span class="highlight">$1</span>', e($text)));
    }
}
