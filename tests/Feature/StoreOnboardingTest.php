<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\StoreRole;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_also_creates_a_store_and_owner_membership(): void
    {
        $user = app(CreateNewUser::class)->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $store = Store::query()->where('owner_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'juan@example.com',
        ]);

        $this->assertDatabaseHas('store_user', [
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => StoreRole::Owner->value,
        ]);
    }
}
