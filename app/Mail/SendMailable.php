<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $referrer;

    /**
     * Create a new message instance.
     *
     * @param  array  $referrer
     * @return void
     */
    public function __construct($referrer)
    {
        $this->referrer = $referrer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Hayzee Computer Resources Referrer Email Verification')
        ->view('mail.verify')
                    ->with('referrer', $this->referrer);
    }
}
