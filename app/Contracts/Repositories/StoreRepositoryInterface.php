<?php

namespace App\Contracts\Repositories;

use App\Enums\StoreRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Collection;

interface StoreRepositoryInterface
{
    /**
     * @return Collection<int, Store>
     */
    public function forUser(User $user): Collection;

    public function ownedCount(User $user): int;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Store;

    public function attachUser(Store $store, User $user, StoreRole $role): void;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Store $store, array $attributes): Store;

    /**
     * @return Collection<int, User>
     */
    public function staffForStore(Store $store): Collection;

    public function userBelongsToStore(Store $store, int $userId): bool;
}
