<?php

namespace App\Http\Resources;

use App\Models\OneTimeLoginLink;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentOTLResource extends JsonResource {

  public static $wrap = null;

  public function toArray($request): array
  {
    /** @var OneTimeLoginLink $this */
    return [
//      'uuid'       => $this->uuid,
      'login_link' => $this->loginLink,
      'expire_at'  => $this->expire_at,
    ];
  }
}
