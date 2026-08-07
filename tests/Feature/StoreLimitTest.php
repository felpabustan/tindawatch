<?php

namespace Tests\Feature;

use App\Enums\StoreRole;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreContext;
use Tests\TestCase;

class StoreLimitTest extends TestCase
{
    use CreatesStoreContext;
    use RefreshDatabase;

    public function test_owner_cannot_create_more_than_three_stores(): void
    {
        config(['tindawatch.max_stores_per_user' => 3]);

        [$owner] = $this->createOwnerWithStore(['name' => 'Store 1']);

        for ($i = 2; $i <= 3; $i++) {
            $store = Store::factory()->create([
                'owner_id' => $owner->id,
                'name' => "Store {$i}",
            ]);
            $store->users()->attach($owner->id, ['role' => StoreRole::Owner->value]);
        }

        $this->assertSame(3, $owner->ownedStores()->count());

        $this->actingAs($owner)
            ->post(route('stores.store'), [
                'name' => 'Store 4',
                'address' => 'Somewhere',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(3, $owner->ownedStores()->count());
    }

    public function test_owner_can_create_store_under_the_limit(): void
    {
        config(['tindawatch.max_stores_per_user' => 3]);

        [$owner] = $this->createOwnerWithStore(['name' => 'Store 1']);

        $this->actingAs($owner)
            ->post(route('stores.store'), [
                'name' => 'Store 2',
            ])
            ->assertRedirect(route('stores.index'));

        $this->assertSame(2, $owner->ownedStores()->count());
    }
}
