<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\URL;

class ValidateApiSignature {

  /**
   * Handle an incoming request.
   *
   * @param Request $request
   * @param Closure $next
   *
   * @return Response
   */
  public function handle(Request $request, Closure $next)
  {
    /** @var Request $clone */
    $clone = $request->duplicate($request->only(['signature', 'expires']));
    // FIXME: any domain signature is valid?
    if (URL::hasValidSignature($clone, false)) {
        return $next($request);
    }
    throw new InvalidSignatureException;
  }
}
