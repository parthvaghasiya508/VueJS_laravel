<?php

namespace App\Http\Middleware;

use App\Lib\Cultuzz;
use Closure;
use Illuminate\Http\Request;

class SwitchLanguage
{
  /**
   * Handle an incoming request.
   *
   * @param Request $request
   * @param Closure $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    $al = strtolower(trim($request->header('accept-language', Cultuzz::FALLBACK_LANG)));
    $lang = in_array($al, Cultuzz::LANGS) ? $al : Cultuzz::FALLBACK_LANG;
    app()->setLocale($lang);
    return $next($request);
  }
}
