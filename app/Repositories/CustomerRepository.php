<?php

namespace App\Repositories;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function paginateForStore(Store $store, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return Customer::query()
            ->where('store_id', $store->id)
            ->when($search, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listForStore(Store $store): Collection
    {
        return Customer::query()
            ->where('store_id', $store->id)
            ->orderBy('name')
            ->get(['id', 'name', 'credit_balance']);
    }

    public function create(array $attributes): Customer
    {
        return Customer::query()->create($attributes);
    }

    public function findForStoreOrFail(Store $store, int $customerId): Customer
    {
        return Customer::query()
            ->where('store_id', $store->id)
            ->findOrFail($customerId);
    }

    public function findForUpdate(int $customerId): Customer
    {
        return Customer::query()->lockForUpdate()->findOrFail($customerId);
    }

    public function loadShowRelations(Customer $customer): Customer
    {
        return $customer->load([
            'sales' => fn ($q) => $q->latest()->limit(20),
            'creditPayments' => fn ($q) => $q->latest()->limit(20),
        ]);
    }

    public function sumOpenUtang(Store $store): int
    {
        return (int) Customer::query()
            ->where('store_id', $store->id)
            ->where('credit_balance', '>', 0)
            ->sum('credit_balance');
    }

    public function incrementCreditBalance(Customer $customer, int $amount): void
    {
        $customer->increment('credit_balance', $amount);
    }

    public function decrementCreditBalance(Customer $customer, int $amount): void
    {
        $customer->decrement('credit_balance', $amount);
    }

    public function createCreditPayment(array $attributes): CreditPayment
    {
        return CreditPayment::query()->create($attributes);
    }

    public function withOpenBalance(Store $store): Collection
    {
        return Customer::query()
            ->where('store_id', $store->id)
            ->where('credit_balance', '>', 0)
            ->orderByDesc('credit_balance')
            ->get(['id', 'name', 'contact', 'credit_balance']);
    }

    public function creditPaymentsBetween(Store $store, CarbonInterface $from, CarbonInterface $to, int $limit = 100): Collection
    {
        return CreditPayment::query()
            ->with(['customer:id,name', 'receiver:id,name'])
            ->where('store_id', $store->id)
            ->whereBetween('paid_at', [$from, $to])
            ->latest('paid_at')
            ->limit($limit)
            ->get();
    }
}
