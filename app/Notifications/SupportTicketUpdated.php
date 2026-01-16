<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $sender;
    protected $messageContent;

    public function __construct(SupportTicket $ticket, User $sender, string $messageContent)
    {
        $this->ticket = $ticket;
        $this->sender = $sender;
        $this->messageContent = $messageContent;
    }

    public function via(object $notifiable): array
    {
        // Store in DB for "Bell Icon", Send Email for visibility
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $role = $this->sender->role === 'super_admin' ? 'Support Team' : 'User';

        return (new MailMessage)
                    ->subject("New Reply on Ticket #{$this->ticket->id} - {$this->ticket->subject}")
                    ->greeting("Hello {$notifiable->first_name},")
                    ->line("There is a new reply from {$this->sender->first_name} ({$role}).")
                    ->line('"' . \Illuminate\Support\Str::limit($this->messageContent, 100) . '"')
                    ->action('View Ticket', url("/dashboard/support/{$this->ticket->id}"))
                    ->line('Thank you for using TinExpress.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'sender_name' => $this->sender->first_name,
            'message_preview' => \Illuminate\Support\Str::limit($this->messageContent, 50),
            'action_url' => "/dashboard/support/{$this->ticket->id}"
        ];
    }
}