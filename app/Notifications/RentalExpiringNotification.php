<?php

namespace App\Notifications;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class RentalExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Rental $rental
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
        return (new MailMessage)
            ->subject(__('messages.rental_expiring_subject'))
            ->greeting(__('messages.rental_expiring_greeting', ['name' => $notifiable->first_name]))
            ->line(__('messages.rental_expiring_line_1', ['date' => $this->rental->end_date->format('d.m.Y')]))
            ->line('**' . $this->rental->book->title . '**')
            ->line(__('messages.rental_expiring_line_2'))
            ->action(__('messages.rental_expiring_action'), url('/my-rentals')); 
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
