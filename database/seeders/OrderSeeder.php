<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
    use App\Models\Order;
use App\Models\OrderItem;


class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

public function run()
{
    $order = Order::create([
        'user_id' => 2,
        'total_price' => 1200,
        'status' => 'completed'
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => 1,
        'quantity' => 1,
        'price' => 1200
    ]);
}
}
