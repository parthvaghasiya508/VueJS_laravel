<?php

namespace App\Http;

use App\Http\Middleware\AgentThrottleRequests;
use App\Http\Middleware\EnsureAgentIsValid;
use App\Http\Middleware\EnsureDetailsIsFilled;
use App\Http\Middleware\EnsureDomainIsValid;
use App\Http\Middleware\EnsureHotelIsValid;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\Permission;
use App\Http\Middleware\RoomDBHasAccess;
use App\Http\Middleware\SwitchLanguage;
use App\Http\Middleware\ValidateApiSignature;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {

  /**
   * The application's global HTTP middleware stack.
   *
   * These middleware are run during every request to your application.
   *
   * @var array
   */
  protected $middleware = [
    \App\Http\Middleware\TrustProxies::class,
    \Fruitcake\Cors\HandleCors::class,
    \App\Http\Middleware\CheckForMaintenanceMode::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    SwitchLanguage::class,
  ];

  /**
   * The application's route middleware groups.
   *
   * @var array
   */
  protected $middlewareGroups = [
    'web' => [
      'domain.check',
      \App\Http\Middleware\EncryptCookies::class,
      \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
      \Illuminate\Session\Middleware\StartSession::class,
      // \Illuminate\Session\Middleware\AuthenticateSession::class,
      \Illuminate\View\Middleware\ShareErrorsFromSession::class,
      \App\Http\Middleware\VerifyCsrfToken::class,
      \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'api' => [
      'domain.check',
      'sanctum.session',
      'throttle:api',
      \Illuminate\Routing\Middleware\SubstituteBindings::class,
      SwitchLanguage::class,
    ],

    'api3' => [
      'throttle:60,1',
      \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'agents' => [
      'agent.check',
      'throttle:agents',
      'bindings',
    ],

    'roomdb' => [
      'roomdb.check'
    ]
  ];

  /**
   * The application's route middleware.
   *
   * These middleware may be assigned to groups or used individually.
   *
   * @var array
   */
  protected $routeMiddleware = [
    'auth'             => \App\Http\Middleware\Authenticate::class,
    'auth.basic'       => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
    'bindings'         => \Illuminate\Routing\Middleware\SubstituteBindings::class,
    'cache.headers'    => \Illuminate\Http\Middleware\SetCacheHeaders::class,
    'can'              => \Illuminate\Auth\Middleware\Authorize::class,
    'guest'            => \App\Http\Middleware\RedirectIfAuthenticated::class,
    'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
    'signed'           => ValidateApiSignature::class,
    'throttle'         => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'verified'         => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    'details_filled'   => EnsureDetailsIsFilled::class,
    'switch_language'  => SwitchLanguage::class,
    'hotel.check'      => EnsureHotelIsValid::class,
    'admin.check'      => EnsureUserIsAdmin::class,
    'domain.check'     => EnsureDomainIsValid::class,
    'permission'       => Permission::class,
    'agent.check'      => EnsureAgentIsValid::class,
    'agent.throttle'   => AgentThrottleRequests::class,
    'sanctum.session'  => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'roomdb.check'     => RoomDBHasAccess::class
  ];
}
