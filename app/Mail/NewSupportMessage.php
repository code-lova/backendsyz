<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewSupportMessage extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The support message data for the email.
     *
     * @var array
     */
    public $mailData;

    /**
     * Create a new message instance.
     *
     * @param array $mailData
     */
    public function __construct(array $mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Support Ticket Submitted',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.support_message',
            with: [
                'user_name' => $this->mailData['user_name'],
                'user_email' => $this->mailData['user_email'], 
                'subject' => $this->mailData['subject'],
                'support_message' => $this->mailData['support_message'],
                'reference' => $this->mailData['reference'] ?? '',
            ]
        );
    }
}
