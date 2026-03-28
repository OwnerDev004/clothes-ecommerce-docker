<?php

namespace App\Services\Api\V1\Queue;

use App\Jobs\SendingWelcomeMailNewsLetterJob;
use App\Models\NewsLetterSubscribe;

class NewsletterSubscribeService
{

    public function sendMailSubscriber(array $validated)
    {
        $email = trim((string) ($validated['email'] ?? ''));

        $subscriber = NewsLetterSubscribe::query()->firstOrCreate([
            'email' => $email,
        ]);

        if (!$subscriber->wasRecentlyCreated) {
            return true;
        }

        SendingWelcomeMailNewsLetterJob::dispatch($subscriber['email'])->afterCommit();
        return true;
    }
}
