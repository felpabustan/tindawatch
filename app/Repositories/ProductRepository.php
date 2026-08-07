<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginateForStore(Store $store, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return Product::query()
            ->with('category:id,name')
            ->where('store_id', $store->id)
            ->when($search, function ($query, $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function inStockForStore(Store $store): Collection
    {
        return Product::query()
            ->where('store_id', $store->id)
            ->where('stock_qty', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sell_price', 'stock_qty', 'unit']);
    }

    public function create(array $attributes): Product
    {
        return Product::query()->create($attributes);
    }

    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function findForStoreOrFail(Store $store, int $productId): Product
    {
        return Product::query()
            ->where('store_id', $store->id)
            ->findOrFail($productId);
    }

    public function findForUpdate(int $productId): Product
    {
        return Product::query()->lockForUpdate()->findOrFail($productId);
    }

    public function findForStoreForUpdate(Store $store, int $productId): Product
    {
        return Product::query()
            ->where('store_id', $store->id)
            ->lockForUpdate()
            ->findOrFail($productId);
    }

    public function updateStock(Product $product, int $stockQty): Product
    {
        $product->update(['stock_qty' => $stockQty]);

        return $product;
    }

    public function countLowStock(Store $store): int
    {
        return Product::query()
            ->where('store_id', $store->id)
            ->whereColumn('stock_qty', '<=', 'reorder_threshold')
            ->count();
    }

    public function inventoryForStore(Store $store): Collection
    {
        return Product::query()
            ->where('store_id', $store->id)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock_qty', 'cost_price', 'sell_price', 'reorder_threshold']);
    }

    public function createStockMovement(
        Store $store,
        Product $product,
        User $user,
        StockMovementType $type,
        int $quantity,
        ?string $reason = null,
    ): StockMovement {
        return StockMovement::query()->create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $type === StockMovementType::Adjustment ? $quantity : abs($quantity),
            'reason' => $reason,
            'created_by' => $user->id,
        ]);
    }
}
