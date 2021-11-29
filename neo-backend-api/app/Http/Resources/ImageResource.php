<?php

namespace App\Http\Resources;

use App\Models\Image;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource {

  /**
   * Transform the resource into an array.
   *
   * @param \Illuminate\Http\Request $request
   *
   * @return array
   */
  public function toArray($request)
  {
    /** @var Image $this */
    $ret = parent::toArray($this);
    if ($this->relationLoaded('rooms')) {
      $ret['rooms'] = $this->rooms->pluck('room_id');
    }
    return $ret;
  }
}
