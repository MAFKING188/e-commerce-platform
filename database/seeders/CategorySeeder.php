<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CatalogDelivery\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Electronics',
            'Clothing',
            'Home & Kitchen',
            'Books'
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category]);
        }
    }
}
