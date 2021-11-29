<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentUserResource extends JsonResource {

  public static $wrap = null;

  public static function collection($resource): AgentUsersCollection
  {
    return new AgentUsersCollection(parent::collection($resource));
  }

  public function toArray($request): array
  {
    /** @var User $this */
    return [
      'id'         => $this->agent_user_id,
      'email'      => $this->email,
      'profile'    => $this->profile,
      'avatar'     => $this->avatar,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
//      'hotels'     => $this->hotels,
    ];
  }
}
