<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\SendMailable;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Access variables from the array
        $referrer = $this->data['referrer'];
        $user = $this->data['user'];

        // Code to send email
       
        $email = new SendMailable($referrer);
        Mail::to($user['email'])->send($email);
    }
    // public function handle()
    // {
    //     $email = new SendMailable($this->referrer);
    //     Mail::to($this->referrer['email'])->send($email);
    // }
}
