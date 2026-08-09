<?php

namespace App\Contracts\Repositories;

use App\Models\EwalletTransaction;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EwalletTransactionRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, EwalletTransaction>
     */
    public function paginateForStore(Store $store, int $perPage = 20): LengthAwarePaginator;

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
     * @return Collection<int, object{provider: mixed, type: mixed, count: mixed, amount: mixed, fees: mixed}>
     */
    public function totalsByProviderBetween(Store $store, CarbonInterface $from, CarbonInterface $to): Collection;
}
