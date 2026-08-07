<?php

namespace App\Contracts\Repositories;

use App\Models\EwalletTransaction;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EwalletTransactionRepositoryInterface
{
    public function paginateForStore(Store $store, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return object{count: int|string|null, amount: int|string|null, fees: int|string|null}|null
     */
    public function totalsSince(Store $store, CarbonInterface $since): ?object;

    /**
     * @return array<string, int|string>
     */
    public function dailyTotalsSince(Store $store, CarbonInterface $since): array;

    /**
     * @return Collection<int, EwalletTransaction>
     */
    public function recentForStore(Store $store, int $limit = 6): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): EwalletTransaction;

    /**
     * @return Collection<int, object>
     */
    public function totalsByProviderBetween(Store $store, CarbonInterface $from, CarbonInterface $to): Collection;
}
