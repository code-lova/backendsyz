<?php

namespace App\Mail;

use App\Models\BookingAppt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $recipient;
    public $recipientType;
    public $newStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(BookingAppt $appointment, $recipient, string $recipientType, string $newStatus)
    {
        $this->appointment = $appointment;
        $this->recipient = $recipient;
        $this->recipientType = $recipientType;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusText = ucfirst(strtolower($this->newStatus));
        
        return new Envelope(
            subject: "Booking Status Update - {$statusText} | {$this->appointment->booking_reference}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_status_update',
            with: [
                'appointment' => $this->appointment,
                'recipient' => $this->recipient,
                'recipientType' => $this->recipientType,
                'newStatus' => $this->newStatus,
            ]
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
