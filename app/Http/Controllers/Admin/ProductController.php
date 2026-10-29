<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Course;
use App\Models\OrderBump;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with(['courses:id,title', 'orderBumps.bumpProduct:id,name'])
            ->withCount(['orders as approved_orders_count' => fn (Builder $orders) => $orders->where('status', OrderStatus::Approved)])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.products.index', [
            'products' => $products,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'product' => new Product(['is_active' => true, 'checkout_timer_minutes' => config('checkout.default_timer_minutes')]),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'bumpCandidates' => Product::query()->where('delivered_by_hotmart', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $product = Product::query()->create($this->productAttributes($request));
            $product->courses()->sync($request->validated('course_ids', []));
            $this->syncOrderBumps($product, $request->validated('bumps', []));
        });

        return redirect()->route('admin.products.index')->with('status', 'Produto criado.');
    }

    public function edit(Product $product): View
    {
        $approved = fn (Builder $orders) => $orders->where('status', OrderStatus::Approved);

        $product->load([
            'courses:id',
            'orderBumps',
            'offers' => fn ($offers) => $offers
                ->with('bumpOverrides.orderBump.bumpProduct:id,name')
                ->withCount(['orders as approved_orders_count' => $approved])
                ->withSum(['orders as approved_revenue' => $approved], 'amount')
                ->latest(),
        ]);

        return view('admin.products.edit', [
            'product' => $product,
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'bumpCandidates' => Product::query()->whereKeyNot($product->id)->where('delivered_by_hotmart', false)->orderBy('name')->get(['id', 'name']),
            'integrationToken' => Setting::integrationApiToken(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            $product->update($this->productAttributes($request, $product));
            $product->courses()->sync($request->validated('course_ids', []));
            $this->syncOrderBumps($product, $request->validated('bumps', []));
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'Produto atualizado.');
    }

    /**
     * Products with orders are kept for the sales history; deactivate them instead.
     */
    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orders()->exists()) {
            return back()->with('error', 'Este produto já tem pedidos. Desative-o em vez de excluir.');
        }

        if ($product->checkout_banner) {
            Storage::disk('public')->delete($product->checkout_banner);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Produto excluído.');
    }

    /**
     * Validated attributes plus the checkout banner upload/removal.
     *
     * @return array<string, mixed>
     */
    private function productAttributes(ProductRequest $request, ?Product $product = null): array
    {
        $attributes = $request->safe()->except(['course_ids', 'bumps', 'checkout_banner_upload', 'remove_checkout_banner']);

        if ($request->hasFile('checkout_banner_upload') || $request->validated('remove_checkout_banner')) {
            if ($product?->checkout_banner) {
                Storage::disk('public')->delete($product->checkout_banner);
            }

            $attributes['checkout_banner'] = $request->hasFile('checkout_banner_upload')
                ? $request->file('checkout_banner_upload')->store('checkout', 'public')
                : null;
        }

        // Hotmart charges and delivers: nothing is sold here, so no price or bumps of its own.
        if ($request->validated('delivered_by_hotmart')) {
            $attributes['price'] = 0;
        }

        return $attributes;
    }

    /**
     * Removes the bumps taken off the form, updates the ones kept (so offer prices stay
     * attached) and creates the new ones. Form order becomes display order.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncOrderBumps(Product $product, array $rows): void
    {
        // Removed first, so a bump removed and added again in the same save doesn't clash.
        OrderBump::query()
            ->where('product_id', $product->id)
            ->whereNotIn('id', collect($rows)->pluck('id')->filter()->all())
            ->delete();

        foreach (array_values($rows) as $position => $row) {
            $attributes = [
                'bump_product_id' => $row['bump_product_id'],
                'price' => $row['price'],
                'headline' => $row['headline'],
                'sort_order' => $position,
            ];

            $bump = filled($row['id']) ? OrderBump::query()->where('product_id', $product->id)->find($row['id']) : null;

            if ($bump) {
                $bump->update($attributes);
            } else {
                $product->orderBumps()->create($attributes);
            }
        }
    }
}
