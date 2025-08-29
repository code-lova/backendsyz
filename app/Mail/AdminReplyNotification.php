<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminReplyNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The support ticket reply data for the email.
     *
     * @var array
     */
    public $replyData;

    /**
     * Create a new message instance.
     *
     * @param array $replyData
     */
    public function __construct(array $replyData)
    {
        $this->replyData = $replyData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Support Team Response - Ticket #' . $this->replyData['ticket_reference'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_reply_notification',
            with: $this->replyData,
        );
    }
}
