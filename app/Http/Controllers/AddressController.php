<?php

namespace App\Http\Controllers;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = Address::where('user_id', auth()->id())->get();
    return view('address.index',compact('addresses'));
    }

    public function create()
    {
        return view('addresses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'line1' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string'
        ]);

        Address::create([
            'user_id' => auth()->id(),
            'line1' => $request->line1,
            'line2' => $request->line2,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $request->country
        ]);

        return redirect()->route('addresses.index')->with('success', 'Address added');
    }

    public function destroy()
    {
        Address::destroy($id);

        return redirect()->back()->with('success', 'Address removed');
    }
}