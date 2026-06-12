<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedByAdminNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $plainPassword
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = config('app.frontend_url', url('/')) . '/login';

        return (new MailMessage)
            ->subject(__('messages.email_welcome_subject'))
            ->greeting(__('messages.email_welcome_greeting', ['name' => $notifiable->first_name]))
            ->line(__('messages.email_account_created'))
            ->line(__('messages.email_your_credentials'))
            ->line('**Email:** ' . $notifiable->email)
            ->line('**Password:** ' . $this->plainPassword)
            ->action(__('messages.email_login_button'), $loginUrl)
            ->line(__('messages.email_change_password_note'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
