<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['user_id', 'product_id', 'product_offer_id', 'gateway', 'external_id', 'preference_id', 'payment_method', 'status', 'amount', 'paid_at', 'payload'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'status' => OrderStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The main product of the order (the one the checkout was opened for).
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Campaign offer used on the checkout, if any.
     *
     * @return BelongsTo<ProductOffer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class, 'product_offer_id');
    }

    /**
     * Everything that was bought: the main product plus any order bump.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<WebhookCall, $this>
     */
    public function webhookCalls(): HasMany
    {
        return $this->hasMany(WebhookCall::class);
    }

    /**
     * Products whose courses this order grants. Orders created before items
     * existed (or by tests) fall back to the main product.
     *
     * @return Collection<int, Product>
     */
    public function purchasedProducts(): Collection
    {
        $products = $this->items()->with('product.courses')->get()->pluck('product');

        if ($products->isEmpty() && $this->product) {
            return collect([$this->product->loadMissing('courses')]);
        }

        return $products;
    }
}
