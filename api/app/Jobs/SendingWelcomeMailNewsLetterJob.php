<?php

namespace App\Jobs;

use App\Models\NewsLetterSubscribe;
use App\Notifications\NewsletterSubscribeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendingWelcomeMailNewsLetterJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly string $email)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $subscriber = NewsLetterSubscribe::query()
            ->where('email', $this->email)
            ->first();

        if (!$subscriber) {
            return;
        }

        Notification::route('mail', $this->email)
            ->notify(new NewsletterSubscribeNotification());

        $subscriber->forceFill([
            'mail_sent_date' => now(),
        ])->save();
    }
}
