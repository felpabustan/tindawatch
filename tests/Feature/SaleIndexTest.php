<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesStoreContext;
use Tests\TestCase;

class SaleIndexTest extends TestCase
{
    use CreatesStoreContext;
    use RefreshDatabase;

    public function test_sales_index_defaults_to_today_and_includes_summary(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $this->createSale($store->id, $owner->id, [
            'total_amount' => 2500,
            'payment_method' => PaymentMethod::Cash,
            'created_at' => now(),
        ]);

        $this->createSale($store->id, $owner->id, [
            'total_amount' => 1000,
            'payment_method' => PaymentMethod::Gcash,
            'payment_reference' => 'GC-123',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->where('summary.count', 1)
                ->where('summary.total_amount', 2500)
                ->where('summary.average_amount', 2500)
                ->has('sales.data', 1)
                ->where('filters.from', now()->toDateString())
                ->where('filters.to', now()->toDateString())
                ->has('sales.data.0.date')
                ->has('sales.data.0.time')
                ->where('sales.data.0.payment_label', 'Cash'));
    }

    public function test_sales_can_be_filtered_by_payment_method_and_search(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $customer = Customer::factory()->create([
            'store_id' => $store->id,
            'name' => 'Aling Nena',
        ]);

        $this->createSale($store->id, $owner->id, [
            'customer_id' => $customer->id,
            'total_amount' => 1500,
            'payment_method' => PaymentMethod::Utang,
            'created_at' => now(),
        ]);

        $this->createSale($store->id, $owner->id, [
            'total_amount' => 900,
            'payment_method' => PaymentMethod::Cash,
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('sales.index', [
                'payment_method' => PaymentMethod::Utang->value,
                'q' => 'Nena',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.count', 1)
                ->where('summary.total_amount', 1500)
                ->has('sales.data', 1)
                ->where('sales.data.0.customer', 'Aling Nena')
                ->where('filters.payment_method', 'utang')
                ->where('filters.q', 'Nena'));
    }

    public function test_sales_are_paginated_and_preserve_filters(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        foreach (range(1, 21) as $i) {
            $this->createSale($store->id, $owner->id, [
                'total_amount' => 100 * $i,
                'payment_method' => PaymentMethod::Cash,
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $this->actingAs($owner)
            ->get(route('sales.index', [
                'from' => now()->toDateString(),
                'to' => now()->toDateString(),
                'payment_method' => PaymentMethod::Cash->value,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.count', 21)
                ->where('sales.current_page', 2)
                ->has('sales.data', 1)
                ->where('filters.payment_method', 'cash'));
    }

    public function test_sales_index_is_scoped_to_current_store(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();
        [$otherOwner, $otherStore] = $this->createOwnerWithStore();

        $this->createSale($store->id, $owner->id, [
            'total_amount' => 500,
            'payment_method' => PaymentMethod::Cash,
            'created_at' => now(),
        ]);

        $this->createSale($otherStore->id, $otherOwner->id, [
            'total_amount' => 99900,
            'payment_method' => PaymentMethod::Cash,
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.count', 1)
                ->where('summary.total_amount', 500));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSale(int $storeId, int $userId, array $attributes): Sale
    {
        $createdAt = $attributes['created_at'] ?? now();
        unset($attributes['created_at']);

        $sale = Sale::query()->create([
            'store_id' => $storeId,
            'user_id' => $userId,
            ...$attributes,
        ]);

        $sale->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        return $sale->refresh();
    }
}
