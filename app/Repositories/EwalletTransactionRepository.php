<?php

namespace App\Repositories;

use App\Contracts\Repositories\EwalletTransactionRepositoryInterface;
use App\Models\EwalletTransaction;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EwalletTransactionRepository implements EwalletTransactionRepositoryInterface
{
    public function paginateForStore(Store $store, int $perPage = 20): LengthAwarePaginator
    {
        return EwalletTransaction::query()
            ->with(['provider:id,name', 'processor:id,name'])
            ->where('store_id', $store->id)
            ->latest()
            ->paginate($perPage);
    }

    public function totalsSince(Store $store, CarbonInterface $since): ?object
    {
        return EwalletTransaction::query()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $since)
            ->toBase()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as amount, COALESCE(SUM(service_fee), 0) as fees')
            ->first();
    }

    public function dailyTotalsSince(Store $store, CarbonInterface $since): array
    {
        return EwalletTransaction::query()
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();
    }

    public function recentForStore(Store $store, int $limit = 6): Collection
    {
        return EwalletTransaction::query()
            ->with(['provider:id,name', 'processor:id,name'])
            ->where('store_id', $store->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function create(array $attributes): EwalletTransaction
    {
        return EwalletTransaction::query()->create($attributes);
    }

    public function totalsByProviderBetween(Store $store, CarbonInterface $from, CarbonInterface $to): Collection
    {
        /** @var Collection<int, object{provider: mixed, type: mixed, count: mixed, amount: mixed, fees: mixed}> */
        return EwalletTransaction::query()
            ->join('ewallet_providers', 'ewallet_providers.id', '=', 'ewallet_transactions.provider_id')
            ->where('ewallet_transactions.store_id', $store->id)
            ->whereBetween('ewallet_transactions.created_at', [$from, $to])
            ->groupBy('ewallet_providers.name', 'ewallet_transactions.type')
            ->toBase()
            ->get([
                'ewallet_providers.name as provider',
                'ewallet_transactions.type',
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(ewallet_transactions.amount), 0) as amount'),
                DB::raw('COALESCE(SUM(ewallet_transactions.service_fee), 0) as fees'),
            ]);
    }

    public function totalsForProviderBetween(
        Store $store,
        int $providerId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $rows = EwalletTransaction::query()
            ->where('store_id', $store->id)
            ->where('provider_id', $providerId)
            ->whereBetween('created_at', [$from, $to])
            ->toBase()
            ->selectRaw('type, COUNT(*) as count, COALESCE(SUM(amount), 0) as amount, COALESCE(SUM(service_fee), 0) as fees')
            ->groupBy('type')
            ->get();

        $cashIn = 0;
        $cashOut = 0;
        $fees = 0;
        $count = 0;

        foreach ($rows as $row) {
            $count += (int) $row->count;
            $fees += (int) $row->fees;

            if ($row->type === 'cash_in') {
                $cashIn += (int) $row->amount;
            } else {
                $cashOut += (int) $row->amount;
            }
        }

        return [
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'fees' => $fees,
            'count' => $count,
        ];
    }
}
