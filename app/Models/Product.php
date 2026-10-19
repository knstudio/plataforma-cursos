<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name', 'slug', 'description', 'price', 'access_days', 'hotmart_product_id', 'is_active',
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
     * Extra products offered with a checkbox on this product's checkout, in display order.
     *
     * @return HasMany<OrderBump, $this>
     */
    public function orderBumps(): HasMany
    {
        return $this->hasMany(OrderBump::class)->orderBy('sort_order')->orderBy('id');
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

    /**
     * Order bumps shown on the checkout, with the offer's changes applied.
     *
     * @return Collection<int, OrderBump>
     */
    public function checkoutBumps(?ProductOffer $offer = null): Collection
    {
        $offer?->loadMissing('bumpOverrides');

        return $this->orderBumps
            ->loadMissing('bumpProduct')
            ->filter(fn (OrderBump $bump) => $bump->isAvailableFor($offer))
            ->values();
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
}
