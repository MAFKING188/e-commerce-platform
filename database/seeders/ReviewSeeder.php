<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Review;


class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   
public function run()
{
    Review::create([
        'user_id' => 2, // John
        'product_id' => 1,
        'rating' => 5,
        'comment' => 'Excellent product'
    ]);

    Review::create([
        'user_id' => 3, // Jane
        'product_id' => 1,
        'rating' => 4,
        'comment' => 'Very good'
    ]);
}
}
