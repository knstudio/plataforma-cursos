<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Every notification received from a gateway, kept for troubleshooting.
 */
#[Fillable(['gateway', 'event', 'external_id', 'order_id', 'result', 'message', 'payload'])]
class WebhookCall extends Model
{
    public const RESULT_PROCESSED = 'processed';

    public const RESULT_IGNORED = 'ignored';

    public const RESULT_FAILED = 'failed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function markAs(string $result, ?string $message = null, ?Order $order = null): void
    {
        $this->update([
            'result' => $result,
            'message' => $message,
            'order_id' => $order?->id ?? $this->order_id,
        ]);
    }
}
