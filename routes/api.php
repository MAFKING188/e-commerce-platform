<?php

use Illuminate\Support\Facades\Route;
use Modules\CatalogDelivery\Models\Product;

// 🎯 MISSION 7: THE BRIDGE (API LAYER)

/*
|--------------------------------------------------------------------------
| TODO: Implement Public Catalog Route
| Requirement: Return all products with their categories and images as JSON.
| Hint: Product::with(['category', 'images'])->paginate(15);
|--------------------------------------------------------------------------
*/

Route::get('/catalog', function () {

    return response()->json(

        Product::with([
            'category',
            'images'
        ])->paginate(15)

    );

});