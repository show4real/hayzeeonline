<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendApproval extends Mailable
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
        return $this->subject('Hayzee Computer Resources Approval Pending')
        ->from('test@hayzeeonline.com', 'Hayzee computer Resources')
        ->view('mail.approval_notification')
                    ->with('referrer', $this->referrer);
    }
}
