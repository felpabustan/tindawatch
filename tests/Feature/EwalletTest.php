<?php

namespace Tests\Feature;

use App\Enums\EwalletTransactionType;
use App\Models\EwalletDayClose;
use App\Models\EwalletProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreContext;
use Tests\TestCase;

class EwalletTest extends TestCase
{
    use CreatesStoreContext;
    use RefreshDatabase;

    public function test_cash_in_decreases_float_increases_cash_and_records_fee(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $provider = EwalletProvider::query()->create([
            'store_id' => $store->id,
            'name' => 'GCash',
            'current_float' => 500000,
            'cash_on_hand' => 0,
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
            'cash_on_hand' => 10000,
        ]);

        $this->assertDatabaseHas('ewallet_transactions', [
            'provider_id' => $provider->id,
            'type' => EwalletTransactionType::CashIn->value,
            'amount' => 10000,
            'service_fee' => 1000,
        ]);

        $provider->refresh();
        $this->assertSame(490000, $provider->current_float);
    }

    public function test_cash_out_increases_float_and_decreases_cash(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $provider = EwalletProvider::query()->create([
            'store_id' => $store->id,
            'name' => 'GCash',
            'current_float' => 500000,
            'cash_on_hand' => 20000,
            'low_float_threshold' => 100000,
        ]);

        $this->actingAs($owner)
            ->post(route('ewallet.transactions.store'), [
                'provider_id' => $provider->id,
                'type' => EwalletTransactionType::CashOut->value,
                'amount' => 50.00,
                'service_fee' => 5.00,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ewallet_providers', [
            'id' => $provider->id,
            'current_float' => 505000,
            'cash_on_hand' => 15000,
        ]);
    }

    public function test_cash_out_rejects_insufficient_cash(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $provider = EwalletProvider::query()->create([
            'store_id' => $store->id,
            'name' => 'GCash',
            'current_float' => 500000,
            'cash_on_hand' => 1000,
            'low_float_threshold' => 100000,
        ]);

        $this->actingAs($owner)
            ->post(route('ewallet.transactions.store'), [
                'provider_id' => $provider->id,
                'type' => EwalletTransactionType::CashOut->value,
                'amount' => 50.00,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('ewallet_providers', [
            'id' => $provider->id,
            'current_float' => 500000,
            'cash_on_hand' => 1000,
        ]);
    }

    public function test_fee_does_not_change_float_until_day_close(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $provider = EwalletProvider::query()->create([
            'store_id' => $store->id,
            'name' => 'GCash',
            'current_float' => 3000000,
            'cash_on_hand' => 0,
            'low_float_threshold' => 100000,
        ]);

        $this->actingAs($owner)
            ->post(route('ewallet.transactions.store'), [
                'provider_id' => $provider->id,
                'type' => EwalletTransactionType::CashIn->value,
                'amount' => 18000.00,
                'service_fee' => 100.00,
            ])
            ->assertRedirect();

        $provider->refresh();
        $this->assertSame(1200000, $provider->current_float);
        $this->assertSame(1800000, $provider->cash_on_hand);

        $this->actingAs($owner)
            ->post(route('ewallet.providers.close-day', $provider), [])
            ->assertRedirect();

        $provider->refresh();
        $this->assertSame(1190000, $provider->current_float);
        $this->assertSame(1800000, $provider->cash_on_hand);

        $this->assertDatabaseHas('ewallet_day_closes', [
            'provider_id' => $provider->id,
            'fees_settled' => 10000,
            'closing_float_after_fees' => 1190000,
            'cash_in_total' => 1800000,
        ]);
    }

    public function test_day_cannot_be_closed_twice(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $provider = EwalletProvider::query()->create([
            'store_id' => $store->id,
            'name' => 'GCash',
            'current_float' => 500000,
            'cash_on_hand' => 0,
            'low_float_threshold' => 100000,
        ]);

        $this->actingAs($owner)
            ->post(route('ewallet.providers.close-day', $provider), [])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('ewallet.providers.close-day', $provider), [])
            ->assertSessionHasErrors('close');

        $this->assertSame(1, EwalletDayClose::query()->where('provider_id', $provider->id)->count());
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
                'cash_on_hand' => 50,
                'low_float_threshold' => 50,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ewallet_providers', [
            'store_id' => $store->id,
            'name' => 'GCash',
            'cash_on_hand' => 5000,
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
