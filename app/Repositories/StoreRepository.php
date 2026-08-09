<?php

namespace App\Repositories;

use App\Contracts\Repositories\StoreRepositoryInterface;
use App\Enums\StoreRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Collection;

class StoreRepository implements StoreRepositoryInterface
{
    public function forUser(User $user): Collection
    {
        /** @var Collection<int, Store> $stores */
        $stores = $user->stores()->orderBy('name')->get();

        return $stores;
    }

    public function ownedCount(User $user): int
    {
        return $user->ownedStores()->count();
    }

    public function create(array $attributes): Store
    {
        return Store::query()->create($attributes);
    }

    public function attachUser(Store $store, User $user, StoreRole $role): void
    {
        $store->users()->attach($user->id, ['role' => $role->value]);
    }

    public function update(Store $store, array $attributes): Store
    {
        $store->update($attributes);

        return $store;
    }

    public function staffForStore(Store $store): Collection
    {
        /** @var Collection<int, User> $staff */
        $staff = $store->users()
            ->orderBy('name')
            ->get(['users.id', 'users.name']);

        return $staff;
    }

    public function userBelongsToStore(Store $store, int $userId): bool
    {
        return $store->users()->where('users.id', $userId)->exists();
    }
}
