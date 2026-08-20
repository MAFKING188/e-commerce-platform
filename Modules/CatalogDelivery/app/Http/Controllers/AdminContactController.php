<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\CatalogDelivery\Models\ContactMessage;

class AdminContactController extends Controller
{
    public function index(): View
    {
        $messages = ContactMessage::latest()->paginate(15);

        return view('catalogdelivery::admin.contacts.index', compact('messages'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return redirect()->back()->with('success', 'Message removed.');
    }
}