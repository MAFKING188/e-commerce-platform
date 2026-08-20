<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\CatalogDelivery\Mail\ContactMessageMail;
use Modules\CatalogDelivery\Models\ContactMessage;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'message' => 'required|string|min:10|max:5000',
        ]);

        $contactMessage = ContactMessage::create([
            ...$validated,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        Mail::to(config('shop.contact_email'))->queue(new ContactMessageMail($contactMessage));

        return redirect()->route('contact')->with('success', 'Thank you — your inquiry has been received. We will get back to you shortly.');
    }
}