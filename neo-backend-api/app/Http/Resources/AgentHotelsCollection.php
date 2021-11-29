<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AgentHotelsCollection extends ResourceCollection {

  public static $wrap = false;

  public function toArray($request)
  {
    return $this->collection;
  }
}
