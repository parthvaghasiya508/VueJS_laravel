<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class EnsureAgentIsValid {

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
    $header = $request->header('Authorization');
    if (!Str::startsWith($header, 'Bearer ')) {
      abort(401, 'Authorization Required');
    }
    [, $signature] = explode(' ', $header, 2);
    if (!$signature) {
      abort(401, 'Authorization Required');
    }
    try {
      [$name, $token] = explode(':', base64_decode($signature));
    } catch (Throwable $e) {
      abort(401, 'Authorization Required');
    }
    if (!$agent = Agent::findByName($name)) {
      abort(401, 'Authorization Required');
    }
    if (!$agent->isValidToken($token)) {
      abort(401, 'Authorization Required');
    }
    session()->now('agent', $agent);
    return $next($request);
  }
}
