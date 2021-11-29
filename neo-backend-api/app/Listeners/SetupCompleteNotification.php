<?php

namespace App\Listeners;

use App\Events\SetupComplete;
use App\Mail\SetupComplete as SetupCompleteMail;
use Illuminate\Support\Facades\Mail;

class SetupCompleteNotification {

  /**
   * Handle the event.
   *
   * @param SetupComplete $event
   *
   * @return void
   */
  public function handle(SetupComplete $event)
  {
    $to = config('cultuzz.notify_email');
    if(!$to) return;
    Mail::send(new SetupCompleteMail($event->userId, $to, $event->data));
  }
}
