<?php

namespace App\Http\Resources;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class AgentHotelResource extends JsonResource {

  public static $wrap = null;

  private $data;
  public function __construct($resource, $data = null)
  {
    parent::__construct($resource);
    $this->data = $data;
  }

  public static function collection($resource): AgentHotelsCollection
  {
    return new AgentHotelsCollection(parent::collection($resource));
  }

  public function toArray($request): array
  {
    /** @var Hotel $this */
    $ret = [
      'id'         => $this->id,
      'name'       => $this->name,
      'country'    => $this->country,
      'active'     => $this->active,
      'logo'       => $this->logo,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
    if (is_array($this->data)) {
      $ret = array_merge($ret, $this->data);
      Arr::forget($ret, 'currency_id');
    }
    return $ret;
  }
}
