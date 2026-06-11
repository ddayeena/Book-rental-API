<?php

namespace App\Notifications;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalOverdueNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Rental $rental
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
        $bookTitle = $this->rental->book->title ?? '...';

        return (new MailMessage)
            ->subject(__('messages.email_overdue_subject', ['id' => $this->rental->id]))
            ->greeting(__('messages.email_overdue_greeting', ['name' => $notifiable->name]))
            ->line(__('messages.email_overdue_body', [
                'title' => $bookTitle,
                'date'  => $this->rental->end_date->format('Y-m-d')
            ]))
            ->line(__('messages.email_overdue_warning'))
            ->line(__('messages.email_footer'));
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
