<?php

namespace Tests\Concerns;

use App\Enums\StoreRole;
use App\Models\Store;
use App\Models\User;

trait CreatesStoreContext
{
    /**
     * @return array{0: User, 1: Store}
     */
    protected function createOwnerWithStore(array $storeAttributes = []): array
    {
        $user = User::factory()->create();

        $store = Store::factory()->create([
            'owner_id' => $user->id,
            ...$storeAttributes,
        ]);

        $store->users()->attach($user->id, ['role' => StoreRole::Owner->value]);

        return [$user, $store];
    }

    /**
     * @return array{0: User, 1: Store}
     */
    protected function createStaffForStore(Store $store): array
    {
        $user = User::factory()->create();
        $store->users()->attach($user->id, ['role' => StoreRole::Staff->value]);

        return [$user, $store];
    }
}
