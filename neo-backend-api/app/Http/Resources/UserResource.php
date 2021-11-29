<?php

namespace App\Http\Resources;

use App\Models\OneTimeLoginLink;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class UserResource extends JsonResource {

  public static $wrap = null;

  public static function collection($resource)
  {
    return new UsersCollection(parent::collection($resource));
  }

  public function toArray($request)
  {
    /** @var User $this */
    $ret = parent::toArray($request);
    Arr::forget($ret, 'agent_hotels');
    if ($this->relationLoaded('roles')) {
      $ret['roles'] = RoleResource::collection($this->roles);
    }
    if ($this->relationLoaded('all_roles')) {
      $ret['all_roles'] = RoleResource::collection($this->all_roles);
    }
    if ($this->relationLoaded('pages')) {
      $ret['pages'] = $this->pages->where('for_hotel')->pluck('name');
      $ret['apages'] = $this->pages->where('for_hotel', false)->pluck('name');
    }
    /** @var OneTimeLoginLink $link */
    if ($link = $request->session()->get('otl')) {
      $ret['otl'] = $link->only('exit_url');
    }
    return $ret;
  }
}
