<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->company().' Tindahan',
            'address' => fake()->address(),
        ];
    }
}
