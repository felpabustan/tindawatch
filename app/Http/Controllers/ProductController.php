<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithStore;
use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Enums\StockMovementType;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\RecordStockMovement;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    use InteractsWithStore;

    public function __construct(
        private ProductRepositoryInterface $products,
        private CategoryRepositoryInterface $categories,
        private RecordStockMovement $recordStockMovement,
    ) {}

    public function index(Request $request): Response
    {
        $store = $this->currentStore($request);
        $search = $request->string('q')->toString();

        $products = $this->products
            ->paginateForStore($store, $search !== '' ? $search : null)
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'category' => $product->category?->name,
                'category_id' => $product->category_id,
                'pieces_per_case' => $product->pieces_per_case,
                'cost_price' => $product->cost_price,
                'sell_price' => $product->sell_price,
                'cost_price_pesos' => Money::toPesos($product->cost_price),
                'sell_price_pesos' => Money::toPesos($product->sell_price),
                'stock_qty' => $product->stock_qty,
                'reorder_threshold' => $product->reorder_threshold,
                'is_low_stock' => $product->isLowStock(),
            ]);

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $this->categories->listForStore($store),
            'filters' => [
                'q' => $search,
            ],
            'canManage' => $request->user()->canManageCatalog($store),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->ensureCatalogAccess($request);
        $store = $this->currentStore($request);

        return Inertia::render('Products/Form', [
            'product' => null,
            'categories' => $this->categories->listForStore($store),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->ensureCatalogAccess($request);
        $store = $this->currentStore($request);
        $data = $this->payload($request);

        $this->products->create([
            ...$data,
            'store_id' => $store->id,
            'stock_qty' => (int) ($request->validated('stock_qty') ?? 0),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product created.']);

        return to_route('products.index');
    }

    public function edit(Request $request, Product $product): Response
    {
        $this->ensureCatalogAccess($request);
        $store = $this->currentStore($request);
        $this->assertProductStore($product, $store->id);

        return Inertia::render('Products/Form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category_id' => $product->category_id,
                'unit' => $product->unit,
                'pieces_per_case' => $product->pieces_per_case,
                'cost_price' => Money::toPesos($product->cost_price),
                'sell_price' => Money::toPesos($product->sell_price),
                'reorder_threshold' => $product->reorder_threshold,
                'stock_qty' => $product->stock_qty,
            ],
            'categories' => $this->categories->listForStore($store),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->ensureCatalogAccess($request);
        $store = $this->currentStore($request);
        $this->assertProductStore($product, $store->id);

        $this->products->update($product, $this->payload($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product updated.']);

        return to_route('products.index');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->ensureCatalogAccess($request);
        $store = $this->currentStore($request);
        $this->assertProductStore($product, $store->id);

        $this->products->delete($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Product deleted.']);

        return to_route('products.index');
    }

    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        $store = $this->currentStore($request);
        $this->assertProductStore($product, $store->id);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->recordStockMovement->handle(
                $store,
                $product,
                $request->user(),
                StockMovementType::from($validated['type']),
                (int) $validated['quantity'],
                $validated['reason'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Stock updated.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ProductRequest $request): array
    {
        $validated = $request->validated();
        $store = $this->currentStore($request);

        if (! empty($validated['category_id'])) {
            $this->categories->findForStoreOrFail($store, (int) $validated['category_id']);
        }

        return [
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?: null,
            'category_id' => $validated['category_id'] ?: null,
            'unit' => $validated['unit'],
            'pieces_per_case' => $validated['pieces_per_case'] ?: null,
            'cost_price' => Money::toCentavos($validated['cost_price']),
            'sell_price' => Money::toCentavos($validated['sell_price']),
            'reorder_threshold' => (int) $validated['reorder_threshold'],
        ];
    }

    private function assertProductStore(Product $product, int $storeId): void
    {
        if ($product->store_id !== $storeId) {
            abort(404);
        }
    }
}
