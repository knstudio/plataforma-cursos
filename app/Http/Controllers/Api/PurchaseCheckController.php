<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * "Did this phone/e-mail buy product X?" — approved orders, as main product or order bump.
 * Contract: C:\laragon\www\integracao-juliana\CONTRATO.md, section 2.1.
 */
class PurchaseCheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! Setting::isValidIntegrationToken($request->bearerToken())) {
            return response()->json(['erro' => 'Token inválido.'], 401);
        }

        $product = Product::query()->where('slug', (string) $request->query('produto'))->first();

        if (! $product) {
            return response()->json(['erro' => 'Produto não encontrado.'], 404);
        }

        $email = Str::lower(trim((string) $request->query('email')));
        $phones = PhoneNumber::variants($request->query('telefone'));

        if ($email === '' && $phones === []) {
            return response()->json(['erro' => 'Informe telefone ou e-mail.'], 422);
        }

        $comprou = User::query()
            ->where(fn (Builder $users) => $users
                ->when($email !== '', fn (Builder $query) => $query->orWhere('email', $email))
                ->when($phones !== [], fn (Builder $query) => $query->orWhereIn('phone', $phones)))
            ->whereHas('orders', fn (Builder $orders) => $orders
                ->where('status', OrderStatus::Approved)
                ->where(fn (Builder $bought) => $bought
                    ->where('product_id', $product->id)
                    ->orWhereHas('items', fn (Builder $items) => $items->where('product_id', $product->id))))
            ->exists();

        return response()->json(['comprou' => $comprou]);
    }
}
