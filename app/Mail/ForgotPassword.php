<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public function __construct($data)
    {
        $data['url'] = env('CLIENT_HOST');
        $this->data = $data;
    }

    public function build()
    {
        return $this->view('emails.mail-forgotpassword')
                    ->subject("Forgot Password")
                    ->with($this->data);
    }
}
