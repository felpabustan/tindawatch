<?php

namespace App\Contracts\Repositories;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    /**
     * @return Collection<int, Category>
     */
    public function listForStore(Store $store): Collection;

    public function findForStoreOrFail(Store $store, int $categoryId): Category;
}
