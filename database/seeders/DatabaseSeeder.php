<?php

namespace Database\Seeders;

use App\Enums\EwalletTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\StoreRole;
use App\Models\Category;
use App\Models\Customer;
use App\Models\EwalletProvider;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\RecordEwalletTransaction;
use App\Services\RecordSale;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $store = Store::query()->create([
            'owner_id' => $user->id,
            'name' => 'Sample Tindahan',
            'address' => '123 Mabini St, Barangay Sample',
        ]);

        $store->users()->attach($user->id, ['role' => StoreRole::Owner->value]);

        $beverages = Category::query()->create(['store_id' => $store->id, 'name' => 'Beverages']);
        $snacks = Category::query()->create(['store_id' => $store->id, 'name' => 'Snacks']);
        $load = Category::query()->create(['store_id' => $store->id, 'name' => 'Household']);

        $products = collect([
            ['name' => 'Coke 8oz', 'sku' => 'COKE-8', 'category_id' => $beverages->id, 'cost_price' => 1000, 'sell_price' => 1500, 'stock_qty' => 48, 'reorder_threshold' => 12],
            ['name' => 'Sprite 8oz', 'sku' => 'SPR-8', 'category_id' => $beverages->id, 'cost_price' => 1000, 'sell_price' => 1500, 'stock_qty' => 36, 'reorder_threshold' => 12],
            ['name' => 'Lucky Me Beef', 'sku' => 'LM-BEEF', 'category_id' => $snacks->id, 'cost_price' => 800, 'sell_price' => 1200, 'stock_qty' => 60, 'reorder_threshold' => 15],
            ['name' => 'Piattos Cheese', 'sku' => 'PIA-CHE', 'category_id' => $snacks->id, 'cost_price' => 1500, 'sell_price' => 2000, 'stock_qty' => 20, 'reorder_threshold' => 8],
            ['name' => 'Safari Crackers', 'sku' => 'SAF-1', 'category_id' => $snacks->id, 'cost_price' => 600, 'sell_price' => 1000, 'stock_qty' => 40, 'reorder_threshold' => 10],
            ['name' => 'Candles (pair)', 'sku' => 'CND-2', 'category_id' => $load->id, 'cost_price' => 1200, 'sell_price' => 1800, 'stock_qty' => 15, 'reorder_threshold' => 5],
            ['name' => 'Matchbox', 'sku' => 'MCH-1', 'category_id' => $load->id, 'cost_price' => 300, 'sell_price' => 500, 'stock_qty' => 4, 'reorder_threshold' => 5],
            ['name' => 'Mineral Water 500ml', 'sku' => 'H2O-500', 'category_id' => $beverages->id, 'cost_price' => 700, 'sell_price' => 1200, 'stock_qty' => 24, 'reorder_threshold' => 10],
        ])->map(fn (array $data) => Product::query()->create([
            'store_id' => $store->id,
            'unit' => 'piece',
            ...$data,
        ]));

        $customer = Customer::query()->create([
            'store_id' => $store->id,
            'name' => 'Aling Nena',
            'contact' => '09171234567',
            'credit_balance' => 0,
        ]);

        $gcash = EwalletProvider::query()->create([
            'store_id' => $store->id,
            'name' => 'GCash',
            'current_float' => 500000,
            'low_float_threshold' => 100000,
        ]);

        EwalletProvider::query()->create([
            'store_id' => $store->id,
            'name' => 'Maya',
            'current_float' => 250000,
            'low_float_threshold' => 80000,
        ]);

        /** @var RecordSale $recordSale */
        $recordSale = app(RecordSale::class);
        $recordSale->handle(
            $store,
            $user,
            [
                ['product_id' => $products[0]->id, 'quantity' => 2],
                ['product_id' => $products[2]->id, 'quantity' => 3],
            ],
            PaymentMethod::Cash,
        );

        $recordSale->handle(
            $store,
            $user,
            [
                ['product_id' => $products[1]->id, 'quantity' => 1],
            ],
            PaymentMethod::Utang,
            $customer,
        );

        /** @var RecordEwalletTransaction $recordEwallet */
        $recordEwallet = app(RecordEwalletTransaction::class);
        $recordEwallet->handle(
            $store,
            $gcash,
            $user,
            EwalletTransactionType::CashIn,
            100000,
            1000,
            '0918XXXXXXX',
        );
    }
}
