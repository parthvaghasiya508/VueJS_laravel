<?php

namespace App\Listeners;

use App\Events\HotelRegistered;
use App\Mail\HotelRegistered as HotelRegisteredMail;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class HotelRegisteredNotification {

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Handle the event.
   *
   * @param HotelRegistered $event
   *
   * @return void
   */
  public function handle(HotelRegistered $event)
  {
    $to = config('cultuzz.notify_email');
    if (!$to) return;
    Mail::send(new HotelRegisteredMail($event->userId, $to, $event->data));
  }
}
