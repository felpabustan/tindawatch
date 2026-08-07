<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreContext;
use Tests\TestCase;

class SaleAndUtangTest extends TestCase
{
    use CreatesStoreContext;
    use RefreshDatabase;

    public function test_sale_deducts_stock(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'stock_qty' => 10,
            'sell_price' => 1500,
        ]);

        $this->actingAs($owner)
            ->post(route('sales.store'), [
                'payment_method' => PaymentMethod::Cash->value,
                'amount_tendered' => 50.00,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ])
            ->assertRedirect(route('sales.pos'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_qty' => 7,
        ]);

        $this->assertDatabaseHas('sales', [
            'store_id' => $store->id,
            'total_amount' => 4500,
            'payment_method' => PaymentMethod::Cash->value,
            'amount_tendered' => 5000,
            'change_amount' => 500,
        ]);
    }

    public function test_cash_sale_requires_tendered_amount(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'stock_qty' => 5,
            'sell_price' => 1500,
        ]);

        $this->actingAs($owner)
            ->post(route('sales.store'), [
                'payment_method' => PaymentMethod::Cash->value,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasErrors('amount_tendered');
    }

    public function test_cash_sale_rejects_insufficient_tendered(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'stock_qty' => 5,
            'sell_price' => 1500,
        ]);

        $this->actingAs($owner)
            ->post(route('sales.store'), [
                'payment_method' => PaymentMethod::Cash->value,
                'amount_tendered' => 10.00,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_utang_sale_increases_customer_credit_balance(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'stock_qty' => 5,
            'sell_price' => 2000,
        ]);

        $customer = Customer::factory()->create([
            'store_id' => $store->id,
            'credit_balance' => 0,
        ]);

        $this->actingAs($owner)
            ->post(route('sales.store'), [
                'payment_method' => PaymentMethod::Utang->value,
                'customer_id' => $customer->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect(route('sales.pos'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'credit_balance' => 4000,
        ]);
    }

    public function test_gcash_sale_requires_payment_reference(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'stock_qty' => 5,
            'sell_price' => 1500,
        ]);

        $this->actingAs($owner)
            ->post(route('sales.store'), [
                'payment_method' => PaymentMethod::Gcash->value,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasErrors('payment_reference');
    }

    public function test_gcash_sale_stores_payment_reference(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $product = Product::factory()->create([
            'store_id' => $store->id,
            'stock_qty' => 5,
            'sell_price' => 1500,
        ]);

        $this->actingAs($owner)
            ->post(route('sales.store'), [
                'payment_method' => PaymentMethod::Gcash->value,
                'payment_reference' => 'GCASH-998877',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('sales.pos'));

        $this->assertDatabaseHas('sales', [
            'store_id' => $store->id,
            'payment_method' => PaymentMethod::Gcash->value,
            'payment_reference' => 'GCASH-998877',
            'total_amount' => 1500,
        ]);
    }

    public function test_credit_payment_reduces_balance(): void
    {
        [$owner, $store] = $this->createOwnerWithStore();

        $customer = Customer::factory()->create([
            'store_id' => $store->id,
            'credit_balance' => 5000,
        ]);

        $this->actingAs($owner)
            ->post(route('customers.pay', $customer), [
                'amount' => 20.00,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'credit_balance' => 3000,
        ]);

        $this->assertDatabaseHas('credit_payments', [
            'customer_id' => $customer->id,
            'amount' => Money::toCentavos(20),
        ]);
    }
}
