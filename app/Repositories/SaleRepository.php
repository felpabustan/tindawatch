<?php

namespace App\Repositories;

use App\Contracts\Repositories\SaleRepositoryInterface;
use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleRepository implements SaleRepositoryInterface
{
    public function summaryForStore(Store $store, array $filters): array
    {
        $filtered = $this->filteredQuery($store, $filters);

        $count = (clone $filtered)->count();
        $totalAmount = (int) (clone $filtered)->sum('total_amount');

        $byPaymentRows = (clone $filtered)
            ->selectRaw('payment_method, count(*) as count, coalesce(sum(total_amount), 0) as total')
            ->groupBy('payment_method')
            ->toBase()
            ->get()
            ->keyBy(fn (object $row) => (string) $row->payment_method);

        return [
            'total_amount' => $totalAmount,
            'count' => $count,
            'average_amount' => $count > 0 ? (int) round($totalAmount / $count) : 0,
            'by_payment' => collect(PaymentMethod::cases())->map(function (PaymentMethod $method) use ($byPaymentRows) {
                $row = $byPaymentRows->get($method->value);

                return [
                    'method' => $method->value,
                    'count' => (int) ($row->count ?? 0),
                    'total' => (int) ($row->total ?? 0),
                ];
            })->values()->all(),
        ];
    }

    public function paginateForStore(Store $store, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filteredQuery($store, $filters)
            ->with(['user:id,name', 'customer:id,name'])
            ->withCount('items')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function sumSince(Store $store, CarbonInterface $since): int
    {
        return (int) Sale::query()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $since)
            ->sum('total_amount');
    }

    public function countSince(Store $store, CarbonInterface $since): int
    {
        return Sale::query()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function dailyTotalsSince(Store $store, CarbonInterface $since): array
    {
        return Sale::query()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();
    }

    public function recentForStore(Store $store, int $limit = 8): Collection
    {
        return Sale::query()
            ->with(['user:id,name', 'customer:id,name'])
            ->where('store_id', $store->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function create(array $attributes): Sale
    {
        return Sale::query()->create($attributes);
    }

    public function createItem(Sale $sale, array $attributes): void
    {
        $sale->items()->create($attributes);
    }

    public function loadDetails(Sale $sale): Sale
    {
        return $sale->load(['items.product', 'customer', 'user']);
    }

    public function reportBetween(Store $store, CarbonInterface $from, CarbonInterface $to): array
    {
        $base = Sale::query()
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$from, $to]);

        $totalAmount = (int) (clone $base)->sum('total_amount');
        $count = (clone $base)->count();

        $byPaymentRows = (clone $base)
            ->selectRaw('payment_method, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy('payment_method')
            ->toBase()
            ->get()
            ->keyBy(fn (object $row) => (string) $row->payment_method);

        $dailyRows = (clone $base)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
            ->groupBy('day')
            ->toBase()
            ->get()
            ->keyBy(fn (object $row) => Carbon::parse((string) $row->day)->toDateString());

        $daily = [];
        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $date) {
            $key = $date->toDateString();
            $row = $dailyRows->get($key);
            $daily[] = [
                'date' => $key,
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ];
        }

        return [
            'total_amount' => $totalAmount,
            'count' => $count,
            'by_payment' => collect(PaymentMethod::cases())->map(function (PaymentMethod $method) use ($byPaymentRows) {
                $row = $byPaymentRows->get($method->value);

                return [
                    'method' => $method->value,
                    'count' => (int) ($row->count ?? 0),
                    'total' => (int) ($row->total ?? 0),
                ];
            })->values()->all(),
            'daily' => $daily,
        ];
    }

    public function bestsellersBetween(Store $store, CarbonInterface $from, CarbonInterface $to, int $limit = 25): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.store_id', $store->id)
            ->whereBetween('sales.created_at', [$from, $to])
            ->groupBy('sale_items.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(sale_items.quantity)'))
            ->limit($limit)
            ->toBase()
            ->get([
                'sale_items.product_id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.quantity * sale_items.price_at_sale) as revenue'),
            ])
            ->map(fn (object $row) => [
                'product_id' => (int) $row->product_id,
                'name' => (string) $row->name,
                'quantity' => (int) $row->quantity,
                'revenue' => (int) $row->revenue,
            ])
            ->all();
    }

    /**
     * @param  array{from: string, to: string, payment_method: string, q: string, user_id: string}  $filters
     * @return Builder<Sale>
     */
    private function filteredQuery(Store $store, array $filters): Builder
    {
        /** @var Builder<Sale> $query */
        $query = Sale::query()->where('store_id', $store->id);

        $query
            ->whereDate('created_at', '>=', $filters['from'])
            ->whereDate('created_at', '<=', $filters['to']);

        if ($filters['payment_method'] !== '') {
            $query->where('payment_method', $filters['payment_method']);
        }

        if ($filters['user_id'] !== '') {
            $userId = (int) $filters['user_id'];

            if ($store->users()->where('users.id', $userId)->exists()) {
                $query->where('user_id', $userId);
            }
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];

            $query->where(function (Builder $inner) use ($q) {
                $inner->where('payment_reference', 'like', "%{$q}%")
                    ->orWhere('id', is_numeric($q) ? (int) $q : 0)
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$q}%"));
            });
        }

        return $query;
    }
}
