<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendFeedback extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $feedback;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(String $name, String $email, String $feedback)
    {
        $this->name = $name;
        $this->email = $email;
        $this->feedback = $feedback;
    }

    /**
     * Build the message.
     *
     * @return $this
     */ 
    public function build()
    {
        return $this->from('userfeedback@trainspotter.co')->view('email.template');
    }
}
