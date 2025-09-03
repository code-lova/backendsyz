<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AdminReplyHwSupportMsg extends Mailable
{
    use Queueable, SerializesModels;

    public $supportMessage;
    public $reply;
    public $healthWorker;

    /**
     * Create a new message instance.
     */
    public function __construct($supportMessage, $reply, $healthWorker)
    {
        $this->supportMessage = $supportMessage;
        $this->reply = $reply;
        $this->healthWorker = $healthWorker;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: new Address(config('mail.support.address'), config('mail.support.name')),
            subject: 'Response to Your Support Request - ' . $this->supportMessage->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_reply_hw_support',
        );
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
