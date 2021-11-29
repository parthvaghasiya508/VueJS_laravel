<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use RuntimeException;

class AgentThrottleRequests extends ThrottleRequests {

  /**
   * @param Request $request
   *
   * @return string
   *
   * @throws RuntimeException
   */
  protected function resolveRequestSignature($request): string
  {
    if ($header = $request->header('Authorization')) {
      return sha1($header);
    }
    throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
  }
}
