<?php

namespace App\Http\Controllers;
use App\Models\Vendor;

use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::all();
        return view('vendors.index',compact('vendors'));
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        Vendor::create($request->all());

        return redirect()->route('vendors.index')->with('success', 'Vendor created');
    }
}
