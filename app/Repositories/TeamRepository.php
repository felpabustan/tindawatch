<?php

namespace App\Repositories;

use App\Contracts\Repositories\TeamRepositoryInterface;
use App\Enums\StoreRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Collection;

class TeamRepository implements TeamRepositoryInterface
{
    public function membersForStore(Store $store): Collection
    {
        return $store->users()->orderBy('name')->get();
    }

    public function createUser(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function attachMember(Store $store, User $user, StoreRole $role): void
    {
        $store->users()->attach($user->id, ['role' => $role->value]);
    }

    public function updateMemberRole(Store $store, User $user, StoreRole $role): void
    {
        $store->users()->updateExistingPivot($user->id, ['role' => $role->value]);
    }
}
