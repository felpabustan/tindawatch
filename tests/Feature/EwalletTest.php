<?php

namespace Tests\Feature;

use App\Enums\EwalletTransactionType;
use App\Models\EwalletProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreContext;
use Tests\TestCase;

class EwalletTest extends TestCase
{
    use CreatesStoreContext;
    use RefreshDatabase;

    public function test_cash_in_decreases_float_and_records_fee(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $provider = EwalletProvider::query()->create([
            'store_id' => $store->id,
            'name' => 'GCash',
            'current_float' => 500000,
            'low_float_threshold' => 100000,
        ]);

        $this->actingAs($owner)
            ->post(route('ewallet.transactions.store'), [
                'provider_id' => $provider->id,
                'type' => EwalletTransactionType::CashIn->value,
                'amount' => 100.00,
                'service_fee' => 10.00,
                'customer_ref' => '0917',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ewallet_providers', [
            'id' => $provider->id,
            'current_float' => 490000,
        ]);

        $this->assertDatabaseHas('ewallet_transactions', [
            'provider_id' => $provider->id,
            'type' => EwalletTransactionType::CashIn->value,
            'amount' => 10000,
            'service_fee' => 1000,
        ]);
    }

    public function test_only_gcash_and_maya_providers_are_allowed(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $this->actingAs($owner)
            ->post(route('ewallet.providers.store'), [
                'name' => 'GoTyme',
                'current_float' => 100,
                'low_float_threshold' => 50,
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($owner)
            ->post(route('ewallet.providers.store'), [
                'name' => 'GCash',
                'current_float' => 100,
                'low_float_threshold' => 50,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ewallet_providers', [
            'store_id' => $store->id,
            'name' => 'GCash',
        ]);

        $this->actingAs($owner)
            ->post(route('ewallet.providers.store'), [
                'name' => 'GCash',
                'current_float' => 200,
                'low_float_threshold' => 50,
            ])
            ->assertSessionHasErrors('name');
    }
}
