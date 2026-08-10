<?php

namespace App\Contracts\Repositories;

use App\Models\EwalletProvider;
use App\Models\Store;
use Illuminate\Support\Collection;

interface EwalletProviderRepositoryInterface
{
    /**
     * @return Collection<int, EwalletProvider>
     */
    public function listForStore(Store $store): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): EwalletProvider;

    public function findForStoreOrFail(Store $store, int $providerId): EwalletProvider;

    public function findForUpdate(int $providerId): EwalletProvider;

    public function updateFloat(EwalletProvider $provider, int $float): EwalletProvider;

    public function updateBalances(EwalletProvider $provider, int $float, int $cashOnHand): EwalletProvider;
}
