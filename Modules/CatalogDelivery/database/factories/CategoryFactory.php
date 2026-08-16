<?php

namespace Modules\CatalogDelivery\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CatalogDelivery\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true)];
    }
}