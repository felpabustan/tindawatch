<?php

namespace Tests\Unit\Repositories;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Sale;
use App\Repositories\SaleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreContext;
use Tests\TestCase;

class SaleRepositoryTest extends TestCase
{
    use CreatesStoreContext;
    use RefreshDatabase;

    public function test_summary_and_filters_respect_date_payment_and_search(): void
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

        $this->createSale($store->id, $owner->id, [
            'total_amount' => 500,
            'payment_method' => PaymentMethod::Utang,
            'created_at' => now()->subDay(),
        ]);

        $repository = app(SaleRepository::class);

        $filters = [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
            'payment_method' => PaymentMethod::Utang->value,
            'q' => 'Nena',
            'user_id' => '',
        ];

        $summary = $repository->summaryForStore($store, $filters);
        $page = $repository->paginateForStore($store, $filters);

        $this->assertSame(1, $summary['count']);
        $this->assertSame(1500, $summary['total_amount']);
        $this->assertSame(1500, $summary['average_amount']);
        $this->assertCount(1, $page->items());
        $this->assertSame('Aling Nena', $page->items()[0]->customer->name);
    }

    public function test_summary_is_scoped_to_store(): void
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

        $summary = app(SaleRepository::class)->summaryForStore($store, [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
            'payment_method' => '',
            'q' => '',
            'user_id' => '',
        ]);

        $this->assertSame(1, $summary['count']);
        $this->assertSame(500, $summary['total_amount']);
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
