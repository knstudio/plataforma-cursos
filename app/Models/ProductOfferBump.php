<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How a campaign offer changes one of the product's order bumps:
 * a different price, or the bump removed from that offer's checkout.
 */
#[Fillable(['product_offer_id', 'order_bump_id', 'price', 'is_removed'])]
class ProductOfferBump extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_removed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ProductOffer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class, 'product_offer_id');
    }

    /**
     * @return BelongsTo<OrderBump, $this>
     */
    public function orderBump(): BelongsTo
    {
        return $this->belongsTo(OrderBump::class);
    }
}
