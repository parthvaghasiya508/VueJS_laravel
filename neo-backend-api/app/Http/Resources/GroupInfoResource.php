<?php

namespace App\Http\Resources;

use App\Models\Group;
use App\Models\Page;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupInfoResource extends JsonResource {

  public static $wrap = null;

  public function toArray($request)
  {
    /** @var Group $this */
    if (!$this) {
      return [];
    }

    return [
      'name'         => $this->name,
      'logo'         => $this->logo,
      'color_font'   => $this->style['color_font'],
      'color_schema' => $this->style['color_schema'],
      'e_domain'     => $this->e_domain,
      'b_domain'     => $this->b_domain,
      'agent'        => !!$this->agent,
      'config'       => $this->config,
    ];
  }
}
