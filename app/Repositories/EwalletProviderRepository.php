<?php

namespace App\Repositories;

use App\Contracts\Repositories\EwalletProviderRepositoryInterface;
use App\Models\EwalletProvider;
use App\Models\Store;
use Illuminate\Support\Collection;

class EwalletProviderRepository implements EwalletProviderRepositoryInterface
{
    public function listForStore(Store $store): Collection
    {
        return EwalletProvider::query()
            ->where('store_id', $store->id)
            ->get();
    }

    public function create(array $attributes): EwalletProvider
    {
        return EwalletProvider::query()->create($attributes);
    }

    public function findForStoreOrFail(Store $store, int $providerId): EwalletProvider
    {
        return EwalletProvider::query()
            ->where('store_id', $store->id)
            ->findOrFail($providerId);
    }

    public function findForUpdate(int $providerId): EwalletProvider
    {
        return EwalletProvider::query()->lockForUpdate()->findOrFail($providerId);
    }

    public function updateFloat(EwalletProvider $provider, int $float): EwalletProvider
    {
        $provider->update(['current_float' => $float]);

        return $provider;
    }

    public function updateBalances(EwalletProvider $provider, int $float, int $cashOnHand): EwalletProvider
    {
        $provider->update([
            'current_float' => $float,
            'cash_on_hand' => $cashOnHand,
        ]);

        return $provider;
    }
}
