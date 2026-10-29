<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MercadoPago\MercadoPagoException;
use App\Services\MercadoPago\MercadoPagoPaymentSync;
use App\Services\OrderFulfillment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $status = OrderStatus::tryFrom($request->string('status')->toString());
        $gateway = PaymentGateway::tryFrom($request->string('gateway')->toString());

        $orders = Order::query()
            ->with(['user:id,name,email', 'product:id,name', 'offer:id,name'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($gateway, fn (Builder $query) => $query->where('gateway', $gateway))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('external_id', $search)
                        ->orWhereHas('user', fn (Builder $users) => $users
                            ->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'gateway' => $gateway,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user',
            'items.product:id,name',
            'offer',
            'enrollments.course:id,title',
            'webhookCalls' => fn ($calls) => $calls->latest()->limit(30),
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'manualStatuses' => self::manualStatusesFor($order),
        ]);
    }

    /**
     * Re-read the payment from Mercado Pago, for when a notification was missed.
     */
    public function sync(Order $order, MercadoPagoPaymentSync $sync): RedirectResponse
    {
        if ($order->gateway !== PaymentGateway::MercadoPago || blank($order->external_id)) {
            return back()->with('error', 'Este pedido ainda não tem um pagamento do Mercado Pago para consultar.');
        }

        try {
            $order = $sync->sync($order->external_id) ?? $order;
        } catch (MercadoPagoException $exception) {
            report($exception);

            return back()->with('error', 'Falha ao consultar o Mercado Pago: '.$exception->getMessage());
        }

        return back()->with('status', 'Pedido sincronizado. Status atual: '.$order->status->label().'.');
    }

    /**
     * Manual status change, for refunds or chargebacks the gateway never reported (or a test order).
     * Goes through OrderFulfillment, so access, e-mail and the GoatFlow notice behave as with a gateway.
     */
    public function updateStatus(Request $request, Order $order, OrderFulfillment $fulfillment): RedirectResponse
    {
        $status = OrderStatus::from($request->validate([
            'status' => ['required', Rule::in(array_map(fn (OrderStatus $status) => $status->value, self::manualStatusesFor($order)))],
        ])['status']);

        match (true) {
            $status === OrderStatus::Approved => $fulfillment->approve($order),
            $order->status === OrderStatus::Approved => $fulfillment->reverse($order, $status),
            default => $fulfillment->cancel($order),
        };

        return back()->with('status', 'Pedido marcado como '.$status->label().'.');
    }

    /**
     * @return list<OrderStatus>
     */
    private static function manualStatusesFor(Order $order): array
    {
        return match ($order->status) {
            OrderStatus::Approved => [OrderStatus::Refunded, OrderStatus::Chargeback, OrderStatus::Cancelled],
            OrderStatus::Pending => [OrderStatus::Approved, OrderStatus::Cancelled],
            default => [OrderStatus::Approved],
        };
    }
}
