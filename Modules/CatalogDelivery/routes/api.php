<?php

use Illuminate\Support\Facades\Route;
use Modules\CatalogDelivery\Models\Product;

Route::get('/catalog', function () {
    return Product::with(['category', 'images'])->paginate(15);
});
