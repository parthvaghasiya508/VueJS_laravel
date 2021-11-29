<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RolesCollection extends ResourceCollection {

  public static $wrap = null;

  public function toArray($request)
  {
    return $this->collection;
  }
}
