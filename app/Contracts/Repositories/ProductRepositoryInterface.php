<?php

namespace App\Contracts\Repositories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    public function paginateForStore(Store $store, ?string $search = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return Collection<int, Product>
     */
    public function inStockForStore(Store $store): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product;

    public function delete(Product $product): void;

    public function findForStoreOrFail(Store $store, int $productId): Product;

    public function findForUpdate(int $productId): Product;

    public function findForStoreForUpdate(Store $store, int $productId): Product;

    public function updateStock(Product $product, int $stockQty): Product;

    public function countLowStock(Store $store): int;

    /**
     * @return Collection<int, Product>
     */
    public function inventoryForStore(Store $store): Collection;

    public function createStockMovement(
        Store $store,
        Product $product,
        User $user,
        StockMovementType $type,
        int $quantity,
        ?string $reason = null,
    ): StockMovement;
}
