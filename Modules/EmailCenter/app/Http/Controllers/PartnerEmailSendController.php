<?php

namespace Modules\EmailCenter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\EmailCenter\Http\Requests\SendEmailRequest;
use Modules\EmailCenter\Mail\PlatformMail;
use Modules\EmailCenter\Models\EmailLog;
use Modules\EmailCenter\Models\EmailTemplate;
use Modules\EmailCenter\Services\RecipientResolver;
use App\Http\Controllers\Controller;

class PartnerEmailSendController extends Controller
{
    public function compose()
    {
        $buyers = RecipientResolver::resolveForPartner(auth()->id());
        $templates = EmailTemplate::orderBy('name')->get();

        return view('emailcenter::partner.email-compose', [
            'buyers' => $buyers,
            'templates' => $templates,
            'userSearchUrl' => '',
        ]);
    }

    public function send(SendEmailRequest $request)
    {
        // Partners can ONLY message their own buyers: intersect the posted ids
        // with the resolved buyer list — non-buyer ids are silently dropped.
        $recipients = RecipientResolver::resolveForPartner(auth()->id())
            ->whereIn('id', (array) ($request->input('user_ids') ?? []))
            ->values();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'None of the selected recipients are your buyers.');
        }

        if ($recipients->count() > \Modules\EmailCenter\Http\Controllers\EmailSendController::RECIPIENT_CAP) {
            return back()->with('error', 'Too many recipients.');
        }

        $templateId = null;
        if ($request->filled('template_id')) {
            $templateId = EmailTemplate::find($request->input('template_id'))?->id;
        }

        $batchId = (string) Str::uuid();
        $subject = $request->input('subject');

        foreach ($recipients as $recipient) {
            $resolvedSubject = RecipientResolver::replacePlaceholders($subject, $recipient);
            $resolvedBody = RecipientResolver::replacePlaceholders($request->input('body'), $recipient);

            $log = EmailLog::create([
                'batch_id' => $batchId,
                'sender_user_id' => auth()->id(),
                'sender_role' => 'partner',
                'recipient_email' => $recipient->email,
                'template_id' => $templateId,
                'subject' => $resolvedSubject,
                'body_markdown' => $resolvedBody,
                'status' => 'pending',
            ]);

            try {
                Mail::to($recipient->email)->queue(new PlatformMail($resolvedSubject, $resolvedBody, $recipient->name));
                $log->update(['status' => 'sent']);
            } catch (\Throwable $e) {
                $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('partner.email.logs')
            ->with('status', "Queued {$recipients->count()} email(s) to your buyers.");
    }
}