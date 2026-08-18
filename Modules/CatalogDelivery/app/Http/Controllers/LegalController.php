<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;

class LegalController extends Controller
{
    private const PAGES = ['privacy', 'terms', 'shipping', 'returns'];

    public function show(string $page)
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        return view('catalogdelivery::legal.' . $page, ['page' => $page]);
    }
}