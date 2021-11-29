<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\NewUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NewUserNotification implements ShouldQueue {

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Handle the event.
   *
   * @param UserRegistered $event
   *
   * @return void
   */
  public function handle(UserRegistered $event)
  {
    $to = config('cultuzz.notify_email');
    if (!$to) return;
    Mail::send(new NewUser($event, $to));
  }
}
