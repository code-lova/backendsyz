<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewSupportTicketNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $ticketData;

    /**
     * Create a new message instance.
     *
     * @param array $ticketData
     */
    public function __construct($ticketData)
    {
        $this->ticketData = $ticketData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Support Ticket Created - ' . $this->ticketData['reference'])
                    ->view('emails.new_support_ticket_notification')
                    ->with('ticketData', $this->ticketData);
    }
}
