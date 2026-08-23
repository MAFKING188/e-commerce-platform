<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

use App\Services\CurrencyService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('money', function ($expression) {
            return "<?php echo \App\Services\CurrencyService::format($expression); ?>";
        });

        $this->watchQueueFailures();
    }

    /**
     * Ops alerting: every failed queue job lands in the `alerts` log channel;
     * the owner additionally gets at most one email per hour per failure kind
     * so a backlog storm can't flood the inbox (or Gmail's quota).
     */
    private function watchQueueFailures(): void
    {
        \Queue::failing(function (JobFailed $event) {
            $jobName = $event->job ? $event->job->resolveName() : 'unknown';
            $reason = Str::limit($event->exception->getMessage(), 300);

            Log::channel('alerts')->error('Queue job failed', [
                'job' => $jobName,
                'queue' => $event->queue,
                'exception' => $reason,
            ]);

            $throttleKey = 'alerts:mailed:' . sha1($jobName . '|' . $reason);

            if (! Cache::has($throttleKey)) {
                Cache::put($throttleKey, true, now()->addHour());

                $recipient = config('shop.contact_email');

                if ($recipient) {
                    Mail::raw(
                        "A background job failed on SmartShop.\n\nJob: {$jobName}\nQueue: {$event->queue}\nError: {$reason}",
                        function ($message) use ($recipient, $jobName) {
                            $message->to($recipient)->subject('[SmartShop] Failed job: ' . class_basename($jobName));
                        }
                    );
                }
            }
        });
    }
}