<?php

namespace App\Providers;

use App\Managers\PMSManager;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PMSProvider extends ServiceProvider implements DeferrableProvider
{
  /**
   * Register services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(PMSManager::class, function ($app) {
      return new PMSManager($app['config']['cultuzz']);
    });
  }

  /**
   * Bootstrap services.
   *
   * @return array
   */
  public function provides()
  {
    return [PMSManager::class];
  }
}
