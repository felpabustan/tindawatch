<?php

namespace Tests\Feature;

use App\Enums\StoreRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_store_and_owner_membership(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'juan@example.com')->firstOrFail();
        $store = Store::query()->where('owner_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('store_user', [
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => StoreRole::Owner->value,
        ]);
    }
}
