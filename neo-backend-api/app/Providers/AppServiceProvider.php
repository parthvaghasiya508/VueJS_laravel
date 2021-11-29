<?php

namespace App\Providers;

use App\Services\RoomDB\RoomDBService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    $this->app->singleton(
      RoomDBService::class, function ($app) {
      return new RoomDBService();
    }
    );
  }
}
