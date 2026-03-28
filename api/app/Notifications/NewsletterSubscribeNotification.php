<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsletterSubscribeNotification extends Notification
{
    use Queueable;


    public function __construct()
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }


    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('NewsletterSubscribe Notification')
            ->line('Welcome to our site ' . config('app.name'));

    }


}
