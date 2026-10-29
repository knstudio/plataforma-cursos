<?php

namespace App\Services;

use App\Enums\EnrollmentSource;
use App\Enums\OrderStatus;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\AccessGranted;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Single place where a payment status turns into access (or loss of access).
 * Every method is idempotent: gateways resend the same notification often.
 */
class OrderFulfillment
{
    /**
     * Mark the order as paid, grant its courses and e-mail the buyer.
     * Products sold and delivered by Hotmart only get the order recorded (no courses, no e-mail).
     * Returns false when the order was already approved.
     */
    public function approve(Order $order, ?CarbonInterface $paidAt = null): bool
    {
        if ($order->status === OrderStatus::Approved) {
            return false;
        }

        // Mercado Pago sends several notifications for the same payment at the same
        // moment: the row lock makes the later ones wait and see the order approved.
        $approved = DB::transaction(function () use ($order, $paidAt): bool {
            $current = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($current->status === OrderStatus::Approved) {
                $order->setRawAttributes($current->getAttributes(), true);

                return false;
            }

            $order->update([
                'status' => OrderStatus::Approved,
                'paid_at' => $paidAt ?? now(),
            ]);

            $this->grantCourses($order);

            return true;
        });

        if (! $approved) {
            return false;
        }

        // Access is already granted: a mail failure must not make the gateway retry
        // (the retry would find the order approved and never send the e-mail).
        if (! $order->product?->delivered_by_hotmart) {
            try {
                $order->user->notify(new AccessGranted($order));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        // Post-sale WhatsApp flows (GoatFlow), after the response so the gateway isn't kept waiting.
        dispatch(fn () => app(PurchaseWebhookNotifier::class)->notify($order))->afterResponse();

        return true;
    }

    /**
     * Payment still in progress (Pix not paid yet, boleto, analysis...).
     */
    public function markPending(Order $order): void
    {
        if ($order->status === OrderStatus::Cancelled) {
            $order->update(['status' => OrderStatus::Pending]);
        }
    }

    /**
     * Payment rejected or cancelled before it was ever approved.
     */
    public function cancel(Order $order): void
    {
        if ($order->status === OrderStatus::Pending) {
            $order->update(['status' => OrderStatus::Cancelled]);
        }
    }

    /**
     * Refund or chargeback: remove the access this order granted. When another
     * approved order also grants the same course, access moves to that order.
     */
    public function reverse(Order $order, OrderStatus $status): bool
    {
        if ($order->status === $status) {
            return false;
        }

        DB::transaction(function () use ($order, $status): void {
            $order->update(['status' => $status]);

            $order->enrollments()
                ->whereNull('revoked_at')
                ->get()
                ->each(function (Enrollment $enrollment) use ($order): void {
                    $enrollment->update(['revoked_at' => now()]);
                    $this->regrantFromOtherOrders($order, $enrollment->course_id);
                });
        });

        return true;
    }

    private function grantCourses(Order $order): void
    {
        $user = $order->user;

        foreach ($order->purchasedProducts() as $product) {
            $expiresAt = $this->expirationFor($order, $product);

            foreach ($product->courses as $course) {
                $current = $user->enrollments()->where('course_id', $course->id)->first();

                // Keep an active enrollment that already lasts at least as long.
                if ($current?->isActive() && $this->lastsAtLeast($current->expires_at, $expiresAt)) {
                    continue;
                }

                $user->enrollIn($course, EnrollmentSource::Order, $expiresAt, $order);
            }
        }
    }

    private function regrantFromOtherOrders(Order $reversedOrder, int $courseId): void
    {
        $otherOrders = $reversedOrder->user->orders()
            ->where('status', OrderStatus::Approved)
            ->whereKeyNot($reversedOrder->id)
            ->latest('paid_at')
            ->get();

        foreach ($otherOrders as $other) {
            $product = $other->purchasedProducts()->first(fn (Product $product) => $product->courses->contains('id', $courseId));

            if (! $product) {
                continue;
            }

            $expiresAt = $this->expirationFor($other, $product);

            if ($expiresAt?->isPast()) {
                continue;
            }

            $reversedOrder->user->enrollIn($product->courses->firstWhere('id', $courseId), EnrollmentSource::Order, $expiresAt, $other);

            return;
        }
    }

    private function expirationFor(Order $order, Product $product): ?CarbonInterface
    {
        if (! $product->access_days) {
            return null;
        }

        return ($order->paid_at ?? now())->copy()->addDays($product->access_days)->endOfDay();
    }

    /**
     * Whether an existing expiration ($current) is as good as or better than $candidate (null = lifetime).
     */
    private function lastsAtLeast(?CarbonInterface $current, ?CarbonInterface $candidate): bool
    {
        if ($current === null) {
            return true;
        }

        return $candidate !== null && $current->greaterThanOrEqualTo($candidate);
    }
}
