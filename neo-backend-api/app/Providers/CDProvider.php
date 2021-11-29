<?php

namespace App\Providers;

use App\Managers\CDManager;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CDProvider extends ServiceProvider implements DeferrableProvider
{
  /**
   * Register services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->singleton(CDManager::class, function ($app) {
      return new CDManager();
    });
  }

  /**
   * Bootstrap services.
   *
   * @return array
   */
  public function provides()
  {
    return [CDManager::class];
  }
}
