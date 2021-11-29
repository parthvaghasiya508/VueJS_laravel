<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class RoomDBHasAccess {

  /**
   * Handle an incoming request.
   *
   * @param Request $request
   * @param Closure $next
   *
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    if ($request->header('Accept') !== 'application/json') {
      abort(400, 'Bad request');
    }
    if ($request->header('Authorization') !== config('cultuzz.roomdb_extranet_secret')) {
      abort(401, 'Authorization Required');
    }
    return $next($request);
  }
}
