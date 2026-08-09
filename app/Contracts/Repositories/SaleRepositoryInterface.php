<?php

namespace App\Contracts\Repositories;

use App\Models\Sale;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SaleRepositoryInterface
{
    /**
     * @param  array{from: string, to: string, payment_method: string, q: string, user_id: string}  $filters
     * @return array{total_amount: int, count: int, average_amount: int, by_payment: array<int, array{method: string, count: int, total: int}>}
     */
    public function summaryForStore(Store $store, array $filters): array;

    /**
     * @param  array{from: string, to: string, payment_method: string, q: string, user_id: string}  $filters
     * @return LengthAwarePaginator<int, Sale>
     */
    public function paginateForStore(Store $store, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function sumSince(Store $store, CarbonInterface $since): int;

    public function countSince(Store $store, CarbonInterface $since): int;

    /**
     * @return array<string, int|string>
     */
    public function dailyTotalsSince(Store $store, CarbonInterface $since): array;

    /**
     * @return Collection<int, Sale>
     */
    public function recentForStore(Store $store, int $limit = 8): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Sale;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createItem(Sale $sale, array $attributes): void;

    public function loadDetails(Sale $sale): Sale;

    /**
     * @return array{total_amount: int, count: int, by_payment: array<int, array{method: string, count: int, total: int}>, daily: array<int, array{date: string, total: int, count: int}>}
     */
    public function reportBetween(Store $store, CarbonInterface $from, CarbonInterface $to): array;

    /**
     * @return array<int, array{product_id: int, name: string, quantity: int, revenue: int}>
     */
    public function bestsellersBetween(Store $store, CarbonInterface $from, CarbonInterface $to, int $limit = 25): array;
}
