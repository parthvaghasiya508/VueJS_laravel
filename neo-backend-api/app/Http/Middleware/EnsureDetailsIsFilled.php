<?php

namespace App\Http\Middleware;

use App\Contracts\MustFillDetails;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EnsureDetailsIsFilled
{
  /**
   * Handle an incoming request.
   *
   * @param Request $request
   * @param \Closure $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    if (!$request->user() ||
      ($request->user() instanceof MustFillDetails &&
        !$request->user()->hasDetailsFilled())) {
      return $request->expectsJson()
        ? abort(403, 'Your details is not filled.')
        : Redirect::route('details');
    }
    return $next($request);
  }
}
