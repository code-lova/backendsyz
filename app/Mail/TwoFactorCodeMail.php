<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $user;

    public function __construct($user, $code)
    {
        $this->user = $user;
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject('Your Two-Factor Authentication Code')
            ->view('emails.2fa-code')
            ->with([
                'user' => $this->user,
                'code' => $this->code,
            ]);
    }
}
