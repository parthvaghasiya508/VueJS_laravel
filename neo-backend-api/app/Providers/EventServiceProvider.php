<?php

namespace App\Providers;

use App\Events\HotelRegistered;
use App\Events\ProfileFilled;
use App\Events\SetupComplete;
use App\Events\UserRegistered;
use App\Listeners\CreateRoomDBProperty;
use App\Listeners\HotelRegisteredNotification;
use App\Listeners\NewUserNotification;
use App\Listeners\ProfileFilledNotification;
use App\Listeners\SendEmailVerificationNotification;
use App\Listeners\SetupCompleteNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

  /**
   * The event listener mappings for the application.
   *
   * @var array
   */
  protected $listen = [
    UserRegistered::class => [
      SendEmailVerificationNotification::class,
      NewUserNotification::class,
    ],
    ProfileFilled::class => [
      ProfileFilledNotification::class,
    ],
    HotelRegistered::class => [
      HotelRegisteredNotification::class,
      CreateRoomDBProperty::class,
    ],
    SetupComplete::class => [
      SetupCompleteNotification::class,
    ],
  ];

  /**
   * Register any events for your application.
   *
   * @return void
   */
  public function boot()
  {
    parent::boot();
  }
}
