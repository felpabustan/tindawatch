<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'category_id' => null,
            'name' => fake()->words(2, true),
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'unit' => 'piece',
            'pieces_per_case' => null,
            'cost_price' => fake()->numberBetween(500, 5000),
            'sell_price' => fake()->numberBetween(600, 7000),
            'stock_qty' => fake()->numberBetween(10, 100),
            'reorder_threshold' => 5,
        ];
    }
}
