<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UsersCollection extends ResourceCollection {

  public static $wrap = false;

  public function toArray($request)
  {
    return $this->collection;
  }
}
