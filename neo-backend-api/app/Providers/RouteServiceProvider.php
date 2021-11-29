<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouteServiceProvider extends ServiceProvider {

  /**
   * This namespace is applied to your controller routes.
   *
   * In addition, it is set as the URL generator's root namespace.
   *
   * @var string
   */
  protected $namespace = 'App\Http\Controllers';

  /**
   * The path to the "home" route for your application.
   *
   * @var string
   */
  public const HOME = '/';

  /**
   * Define your route model bindings, pattern filters, etc.
   *
   * @return void
   */
  public function boot()
  {
    $this->configureRateLimiting();

    parent::boot();

//    Route::model('agentUser', User::class);
    Route::bind('agentUser', function ($id) {
      /** @var Agent $agent */
      $agent = session('agent');
      if (!$user = User::query()->firstWhere(['agent_id' => $agent->id, 'agent_user_id' => $id])) {
        throw new NotFoundHttpException('User not found');
      }
      return $user;
    });
    Route::bind('agentHotel', function ($id) {
      /** @var Agent $agent */
      $agent = session('agent');
      if (!$hotel = Hotel::query()->firstWhere(['agent_id' => $agent->id, 'id' => $id])) {
        throw new NotFoundHttpException('Hotel not found');
      }
      return $hotel;
    });

  }

  protected function configureRateLimiting()
  {
    RateLimiter::for('api', function (Request $request) {
      /** @var User $user */
      $user = $request->user();
      if (!$user) {
        return Limit::perMinute(60)->by($request->ip());
      }
      if ($user->admin) {
        return Limit::none();
      }
      return Limit::perMinute(300)->by($user->id);
    });

    RateLimiter::for('agents', function (Request $request) {
      return Limit::perMinute(300)->by($request->header('Authorization'));
    });
  }

  /**
   * Define the routes for the application.
   *
   * @return void
   */
  public function map()
  {
    $this->mapApiRoutes();

    $this->mapWebRoutes();

    $this->mapAgentRoutes();

    $this->mapRoomDBRoutes();

    if (config('app.debug')) {
      $this->mapDebugRoutes();
    }
  }

  /**
   * Define the "web" routes for the application.
   *
   * These routes all receive session state, CSRF protection, etc.
   *
   * @return void
   */
  protected function mapWebRoutes()
  {
    Route::middleware('web')
         ->namespace($this->namespace)
         ->group(base_path('routes/web.php'));
  }

  /**
   * Define the "api" routes for the application.
   *
   * These routes are typically stateless.
   *
   * @return void
   */
  protected function mapApiRoutes()
  {
    Route::prefix('api')
         ->middleware('api')
         ->namespace($this->namespace)
         ->group(base_path('routes/api.php'));
  }

  protected function mapAgentRoutes()
  {
    Route::prefix('agent')
         ->middleware('agents')
         ->namespace($this->namespace)
         ->group(base_path('routes/agent.php'));
  }

  protected function mapRoomDBRoutes()
  {
    Route::prefix('roomdb')
      ->middleware('roomdb')
      ->namespace($this->namespace)
      ->group(base_path('routes/roomdb.php'));
  }

  protected function mapDebugRoutes()
  {
    Route::prefix('debug')/*->middleware('domain.check')*/->group(base_path('routes/debug.php'));
  }
}
