<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Products GoatFlow can ask about in the purchase check (2.1).
 * Contract: C:\laragon\www\integracao-juliana\CONTRATO.md, section 2.3.
 */
class ProductListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! Setting::isValidIntegrationToken($request->bearerToken())) {
            return response()->json(['erro' => 'Token inválido.'], 401);
        }

        $produtos = Product::query()
            ->orderBy('name')
            ->get(['slug', 'name', 'is_active'])
            ->map(fn (Product $product) => ['produto' => $product->slug, 'nome' => $product->name, 'ativo' => $product->is_active]);

        return response()->json(['produtos' => $produtos]);
    }
}
