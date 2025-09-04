<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class UserNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $emailData;
    public $processedMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, array $emailData)
    {
        $this->user = $user;
        $this->emailData = $emailData;
        $this->processedMessage = $this->processMessage($emailData['message']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'SupraCarer'),
            subject: $this->emailData['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.user_notification',
            with: [
                'user' => $this->user,
                'emailData' => $this->emailData,
                'processedMessage' => $this->processedMessage,
                'categoryStyles' => $this->getCategoryStyles(),
            ],
        );
    }

    /**
     * Process message content by replacing placeholders
     */
    private function processMessage(string $message): string
    {
        $placeholders = [
            '[Name]' => $this->user->name,
            '[Email]' => $this->user->email,
            '[Date]' => now()->format('F j, Y'),
            '[Time]' => now()->format('g:i A'),
            '[Year]' => now()->format('Y'),
            '[Month]' => now()->format('F'),
            '[Day]' => now()->format('l'),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $message);
    }

    /**
     * Get category-specific styling
     */
    private function getCategoryStyles(): array
    {
        $styles = [
            'general' => [
                'primary_color' => '#013e5b',
                'secondary_color' => '#3b82f6',
                'accent_color' => '#1e40af',
                'icon' => '📧',
                'gradient' => 'linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%)'

            ],
            'promotional' => [
                'primary_color' => '#013e5b',
                'secondary_color' => '#ef4444',
                'accent_color' => '#b91c1c',
                'icon' => '🎉',
                'gradient' => 'linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%)'
            ],
            'notification' => [
                'primary_color' => '#059669',
                'secondary_color' => '#10b981',
                'accent_color' => '#047857',
                'icon' => '🔔',
                'gradient' => 'linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%)'
            ],
            'reminder' => [
                'primary_color' => '#d97706',
                'secondary_color' => '#f59e0b',
                'accent_color' => '#b45309',
                'icon' => '⏰',
                'gradient' => 'linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%)'
            ],
            'welcome' => [
                'primary_color' => '#7c3aed',
                'secondary_color' => '#8b5cf6',
                'accent_color' => '#6d28d9',
                'icon' => '👋',
                'gradient' => 'linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%)'
            ],
            'update' => [
                'primary_color' => '#0891b2',
                'secondary_color' => '#06b6d4',
                'accent_color' => '#0e7490',
                'icon' => '📢',
                'gradient' => 'linear-gradient(135deg, #fafcfa 0%, #f0f4f8 100%)'
            ],
        ];

        return $styles[$this->emailData['category']] ?? $styles['general'];
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
