<?php

namespace App\Listeners;

use App\Events\ProfileFilled;
use App\Mail\ProfileFilled as ProfileFilledMail;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProfileFilledNotification {

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Handle the event.
   *
   * @param ProfileFilled $event
   *
   * @return void
   */
  public function handle(ProfileFilled $event)
  {
    $to = config('cultuzz.notify_email');
    if (!$to) return;
    Mail::send(new ProfileFilledMail($event->userId, $to));
  }
}
