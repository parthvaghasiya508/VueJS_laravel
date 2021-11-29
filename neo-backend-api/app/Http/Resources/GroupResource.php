<?php

namespace App\Http\Resources;

use App\Models\Group;
use App\Models\Page;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource {

  public static $wrap = null;

  public static function collection($resource)
  {
    return new GroupsCollection(parent::collection($resource));
  }

  public function toArray($request)
  {
    /** @var Group $this */
    $ret = parent::toArray($request);
    if ($this->relationLoaded('pages')) {
      $ret['pages'] = $this->pages->pluck('name');
    }
    if ($this->relationLoaded('hotels')) {
      $ret['hotels'] = $this->hotels;
    }
    return $ret;
  }
}
