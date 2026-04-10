<?php

namespace App\Notifications;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public UserInvitation $invitation,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url("/invitations/{$this->invitation->token}");

        return (new MailMessage)
            ->subject('You have been invited to the Assessment Platform')
            ->greeting('Hello!')
            ->line('You have been invited to join the Assessment Platform dashboard.')
            ->action('Accept Invitation', $url)
            ->line('This invitation will expire in 7 days.')
            ->line('If you did not expect this invitation, you can ignore this email.');
    }
}
