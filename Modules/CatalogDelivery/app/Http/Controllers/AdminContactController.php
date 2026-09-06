<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\CatalogDelivery\Mail\ContactReplyMail;
use Modules\CatalogDelivery\Models\ContactMessage;
use Modules\CatalogDelivery\Models\ContactReply;

class AdminContactController extends Controller
{
    public function index(): View
    {
        $messages = ContactMessage::with('replies')->latest()->paginate(15);

        return view('catalogdelivery::admin.contacts.index', compact('messages'));
    }

    public function reply(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message = ContactMessage::findOrFail($id);

        $reply = ContactReply::create([
            'contact_message_id' => $message->id,
            'body' => $request->body,
            'admin_name' => $request->user()->name,
            'admin_email' => $request->user()->email,
        ]);

        $message->update(['status' => 'replied']);

        Mail::to($message->email)->send(new ContactReplyMail($message, $reply));

        return redirect()->back()->with('status', 'Reply sent to ' . $message->email);
    }

    public function destroy(int $id): RedirectResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return redirect()->back()->with('success', 'Message removed.');
    }
}
