<?php

namespace App\Contracts\Repositories;

use App\Enums\StoreRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Collection;

interface TeamRepositoryInterface
{
    /**
     * @return Collection<int, User>
     */
    public function membersForStore(Store $store): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createUser(array $attributes): User;

    public function attachMember(Store $store, User $user, StoreRole $role): void;

    public function updateMemberRole(Store $store, User $user, StoreRole $role): void;
}
