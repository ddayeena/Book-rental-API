<?php

namespace App\Notifications;

use App\Models\Rental;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Rental $rental;
    protected ?string $reason;
    
    /**
     * Create a new notification instance.
     */
    public function __construct(Rental $rental, ?string $reason = null)
    {
        $this->rental = $rental;
        $this->reason = $reason;
    }

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
        
        $message = (new MailMessage)
            ->subject(__('messages.email_cancelled_subject', ['id' => $this->rental->id]))
            ->greeting(__('messages.email_cancelled_greeting', ['name' => $notifiable->name]))
            ->line(__('messages.email_cancelled_body', ['title' => $bookTitle]));

        if ($this->reason) {
            $message->line(__('messages.email_cancelled_reason', ['reason' => $this->reason]));
        }

        return $message->line(__('messages.email_footer'));
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
