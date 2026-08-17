<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
       public function run()
{
    $this->call([
        \Modules\IdentityAccess\Database\Seeders\UserSeeder::class,
        \Modules\CatalogDelivery\Database\Seeders\CategorySeeder::class,
        \Modules\CatalogDelivery\Database\Seeders\ProductSeeder::class,
        \Modules\CatalogDelivery\Database\Seeders\ReviewSeeder::class,
        \Modules\MarketplacePipeline\Database\Seeders\OrderSeeder::class,
    ]);
}
}
