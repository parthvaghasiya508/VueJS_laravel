<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable as MailableMail;
use Illuminate\Queue\SerializesModels;

class Mailable extends MailableMail implements ShouldQueue {

  use Queueable, SerializesModels;

  function __construct()
  {
    $this->onQueue('mail');
  }

}
