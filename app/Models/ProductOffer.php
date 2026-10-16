<?php

namespace App\Models;

use Database\Factories\ProductOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A special price for a product, reachable through its own checkout link
 * (e.g. /checkout/treinamento-essencial/whats-outubro) for campaigns.
 */
#[Fillable(['product_id', 'name', 'code', 'price', 'order_bump_price', 'without_order_bump', 'expires_at', 'is_active'])]
class ProductOffer extends Model
{
    /** @use HasFactory<ProductOfferFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'order_bump_price' => 'decimal:2',
            'without_order_bump' => 'boolean',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function checkoutUrl(): string
    {
        return route('checkout.offer.show', [$this->product, $this]);
    }
}
