<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CatalogDelivery\Services\CatalogQueryService;

class ViewController extends Controller
{
    public function home(CatalogQueryService $catalog)
    {
        return view('catalogdelivery::home', $catalog->home());
    }

    public function shop(Request $request, CatalogQueryService $catalog)
    {
        return view('catalogdelivery::shop', [
            'products' => $catalog->shop($request),
            'categories' => $catalog->categories(),
        ]);
    }

    public function collection(CatalogQueryService $catalog)
    {
        return view('catalogdelivery::collection', $catalog->collection());
    }

    public function product($id, CatalogQueryService $catalog)
    {
        return view('catalogdelivery::product', $catalog->product($id));
    }

    public function about()
    {
        return view('catalogdelivery::about');
    }

    public function contact()
    {
        return view('catalogdelivery::contact');
    }
}
