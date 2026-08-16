<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'price' => fake()->randomFloat(2, 20, 500),
            'description' => fake()->paragraph(),
            'stock' => fake()->numberBetween(1, 50),
            'image' => null,
            'category_id' => Category::factory(),
        ];
    }
}