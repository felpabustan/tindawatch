<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\StoreRole;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesStoreContext;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use CreatesStoreContext;
    use RefreshDatabase;

    public function test_owner_can_view_reports(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'name' => 'Sardines',
            'stock_qty' => 10,
            'cost_price' => 1000,
            'sell_price' => 1500,
        ]);

        $sale = Sale::query()->create([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'total_amount' => 3000,
            'payment_method' => PaymentMethod::Cash,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price_at_sale' => 1500,
        ]);

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('reports.sales.count', 1)
                ->where('reports.sales.total_amount', 3000)
                ->has('reports.bestsellers', 1)
                ->where('reports.bestsellers.0.name', 'Sardines')
                ->where('reports.inventory.total_value', 10000));
    }

    public function test_staff_cannot_view_reports(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();
        [$staff] = $this->createStaffForStore($store);

        $this->actingAs($staff)
            ->get(route('reports.index'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('reports.export', ['type' => 'sales']))
            ->assertForbidden();
    }

    public function test_reports_are_scoped_to_current_store(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $otherOwner = User::factory()->create();
        $otherStore = Store::factory()->create(['owner_id' => $otherOwner->id]);
        $otherStore->users()->attach($otherOwner->id, ['role' => StoreRole::Owner->value]);

        Sale::query()->create([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'total_amount' => 1000,
            'payment_method' => PaymentMethod::Cash,
        ]);

        Sale::query()->create([
            'store_id' => $otherStore->id,
            'user_id' => $otherOwner->id,
            'total_amount' => 999900,
            'payment_method' => PaymentMethod::Cash,
        ]);

        $this->actingAs($owner)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('reports.sales.count', 1)
                ->where('reports.sales.total_amount', 1000));
    }

    public function test_owner_can_download_sales_csv(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        Sale::query()->create([
            'store_id' => $store->id,
            'user_id' => $owner->id,
            'total_amount' => 2500,
            'payment_method' => PaymentMethod::Gcash,
            'payment_reference' => 'REF-1',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('reports.export', [
                'type' => 'sales',
                'from' => now()->subDay()->toDateString(),
                'to' => now()->toDateString(),
            ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Total sales', $content);
        $this->assertStringContainsString('GCash', $content);
    }

    public function test_owner_can_download_utang_pdf(): void
    {
        [$owner, $store] = $this->createOwnerWithStore(['name' => 'Aling Nena']);

        $customer = \App\Models\Customer::factory()->create([
            'store_id' => $store->id,
            'name' => 'Juan Dela Cruz',
            'credit_balance' => 25000,
        ]);

        $response = $this->actingAs($owner)
            ->get(route('reports.utang.pdf'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('content-type'),
        );

        $customerResponse = $this->actingAs($owner)
            ->get(route('reports.utang.customer-pdf', $customer));

        $customerResponse->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $customerResponse->headers->get('content-type'),
        );
    }

    public function test_staff_cannot_download_utang_pdf(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();
        [$staff] = $this->createStaffForStore($store);

        $this->actingAs($staff)
            ->get(route('reports.utang.pdf'))
            ->assertForbidden();
    }
}
