<?php

namespace App\Repositories;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Support\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function listForStore(Store $store): Collection
    {
        return Category::query()
            ->where('store_id', $store->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function findForStoreOrFail(Store $store, int $categoryId): Category
    {
        return Category::query()
            ->where('store_id', $store->id)
            ->findOrFail($categoryId);
    }
}
