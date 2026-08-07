<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreContext;
use Tests\TestCase;

class ProductScopingTest extends TestCase
{
    use CreatesStoreContext;
    use RefreshDatabase;

    public function test_user_cannot_see_products_from_another_store(): void
    {
        [$ownerA, $storeA] = $this->createOwnerWithStore(['name' => 'Store A']);
        [, $storeB] = $this->createOwnerWithStore(['name' => 'Store B']);

        $productB = Product::factory()->create([
            'store_id' => $storeB->id,
            'name' => 'Secret Item',
        ]);

        Product::factory()->create([
            'store_id' => $storeA->id,
            'name' => 'Visible Item',
        ]);

        $this->actingAs($ownerA);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Visible Item')
            );

        $this->get(route('products.edit', $productB))->assertNotFound();
    }

    public function test_staff_cannot_update_product_prices(): void
    {
        [, $store] = $this->createOwnerWithStore();
        [$staff] = $this->createStaffForStore($store);

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'sell_price' => 1000,
        ]);

        $this->actingAs($staff)
            ->put(route('products.update', $product), [
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => 'piece',
                'cost_price' => 5,
                'sell_price' => 99,
                'reorder_threshold' => 5,
            ])
            ->assertForbidden();
    }
}
