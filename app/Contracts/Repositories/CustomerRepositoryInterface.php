<?php

namespace App\Contracts\Repositories;

use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CustomerRepositoryInterface
{
    public function paginateForStore(Store $store, ?string $search = null, int $perPage = 20): LengthAwarePaginator;

    /**
     * @return Collection<int, Customer>
     */
    public function listForStore(Store $store): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Customer;

    public function findForStoreOrFail(Store $store, int $customerId): Customer;

    public function findForUpdate(int $customerId): Customer;

    public function loadShowRelations(Customer $customer): Customer;

    public function sumOpenUtang(Store $store): int;

    public function incrementCreditBalance(Customer $customer, int $amount): void;

    public function decrementCreditBalance(Customer $customer, int $amount): void;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createCreditPayment(array $attributes): CreditPayment;

    /**
     * @return Collection<int, Customer>
     */
    public function withOpenBalance(Store $store): Collection;

    /**
     * @return Collection<int, CreditPayment>
     */
    public function creditPaymentsBetween(Store $store, CarbonInterface $from, CarbonInterface $to, int $limit = 100): Collection;
}
