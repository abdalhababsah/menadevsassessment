<?php

namespace App\Notifications;

use App\Models\CandidateEmailVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SendCandidateVerificationEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CandidateEmailVerification $verification,
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
        $url = url("/candidate/verify-email?token={$this->verification->token}");

        return (new MailMessage)
            ->subject('Verify Your Email — Assessment Platform')
            ->greeting('Hello!')
            ->line('Please verify your email address to access your assessment.')
            ->action('Verify Email', $url)
            ->line('This link will expire in 24 hours.')
            ->line('If you did not request this, no further action is required.');
    }
}
