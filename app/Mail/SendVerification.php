<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendVerification extends Mailable
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


    public function build()
    {
        return $this->subject('Hayzee Computer Resources Referrer Email Verification')
         ->from('test@hayzeeonline.com', 'Hayzee Computer Resources')
        ->view('mail.verify')
                    ->with('referrer', $this->referrer);
    }
}
