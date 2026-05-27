<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;

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


/*
|--------------------------------------------------------------------------
| TODO: Implement Protected User Route
| Requirement: Use 'auth:sanctum' middleware to return the authenticated user.
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {

    return response()->json(
        $request->user()
    );

});