<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailVerificationNotification implements ShouldQueue {

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
    $user = $event->user;
    if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
      $user->sendEmailVerificationNotification($event->group);
    }
  }
}
