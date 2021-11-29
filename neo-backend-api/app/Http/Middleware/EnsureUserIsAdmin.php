<?php

namespace App\Http\Middleware;

use App\Managers\PMSManager;
use App\Models\Hotel;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
  /**
   * Handle an incoming request.
   *
   * @param Request $request
   * @param Closure $next
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    /** @var User $user */
    if (!$user = $request->user()) {
      abort(401, 'Unauthenticated');
    }
    if (!$user->admin) {
      abort(403, 'Access Denied');
    }
    return $next($request);
  }
}
