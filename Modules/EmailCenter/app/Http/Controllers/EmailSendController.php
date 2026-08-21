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
use Modules\IdentityAccess\Models\User;
use App\Http\Controllers\Controller;

class EmailSendController extends Controller
{
    public const RECIPIENT_CAP = 100;

    public function compose()
    {
        $templates = EmailTemplate::orderBy('name')->get();
        $groups = [
            'all' => 'All active users',
            'admins' => 'Administrators',
            'partners' => 'Partners',
            'members' => 'Members',
            'newsletter' => 'Newsletter subscribers',
        ];

        return view('emailcenter::admin.email-compose', [
            'templates' => $templates,
            'groups' => $groups,
            'userSearchUrl' => route('admin.users.search'),
        ]);
    }

    public function searchUsers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        return response()->json(
            User::where('status', 'active')
                ->whereNotNull('email_verified_at')
                ->where(fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                ->limit(10)
                ->get(['id', 'name', 'email'])
        );
    }

    public function send(SendEmailRequest $request)
    {
        $recipients = $request->filled('user_ids')
            ? RecipientResolver::resolveForAdmin(null, $request->input('user_ids'))
            : RecipientResolver::resolveForAdmin((string) $request->input('group'));

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No eligible recipients matched that selection.');
        }

        if ($recipients->count() > self::RECIPIENT_CAP) {
            return back()->with('error', 'Too many recipients: the limit is ' . self::RECIPIENT_CAP . ' per send.');
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
                'sender_role' => 'admin',
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

        return redirect()->route('admin.email.logs')
            ->with('status', "Queued {$recipients->count()} email(s) in batch {$batchId}.");
    }
}