<?php

namespace Modules\TelemetryPipeline\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Modules\TelemetryPipeline\Models\EmailLog;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $to = collect($event->message->getTo())
                ->map(fn ($address) => $address->getAddress())
                ->first();

            EmailLog::create([
                'recipient' => $to,
                'subject' => $event->message->getSubject(),
                'status' => 'sent',
            ]);
        });
    }
}
