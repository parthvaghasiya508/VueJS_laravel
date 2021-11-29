<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource {

  public static $wrap = null;
  public static function collection($resource): RolesCollection
  {
    return new RolesCollection(parent::collection($resource));
  }

  /**
   * Transform the resource into an array.
   *
   * @param \Illuminate\Http\Request $request
   *
   * @return array
   */
  public function toArray($request)
  {
    $ret = parent::toArray($request);
    /** @var Role $this */
    if ($this->relationLoaded('pages')) {
      $ret['pages'] = $this->pages->pluck('name');
    }
    return $ret;
  }
}
