<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * <p>Should be used for testing&debugging only!</p>
 * <p>Do <b>not</b> use in production!</b>
 *
 * @package App\Support
 *
 */
class HighOrderCacheProxy {

  /**
   * @var mixed
   */
  private $target;
  private int $ttl;

  public function __construct($target, int $ttl)
  {
    $this->target = $target;
    $this->ttl = $ttl;
  }

  public function __call($method, $arguments)
  {
    $signature = $method.json_encode($arguments);
    return Cache::remember($signature, $this->ttl, fn () => $this->target->{$method}(...$arguments));
  }

}
