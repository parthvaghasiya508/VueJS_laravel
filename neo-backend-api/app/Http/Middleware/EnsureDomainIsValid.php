<?php

namespace App\Http\Middleware;

use App\Support\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EnsureDomainIsValid {

  public function handle(Request $request, $next)
  {
    $domain = Str::replaceFirst('api.', '', $request->getHost());
    $stateful = array_filter(config('sanctum.stateful', []));
    $true = Str::is(collect($stateful)->map(fn ($d) => trim($d))->all(), $domain);
    $valid = $true ? $domain : (Domain::getExtranetDomain());
    config([
      'session.domain'   => '.'.$valid,
      'app.domain'       => $valid,
      'app.frontend_url' => "https://$valid",
    ]);
    $request->attributes->set('domain', $valid);
//    logger()->info(config('app.domain').' == '.$request->getPathInfo());
    return $next($request);
  }

}
