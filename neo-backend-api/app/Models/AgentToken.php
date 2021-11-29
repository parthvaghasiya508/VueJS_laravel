<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class AgentToken
 * @package App\Models
 *
 * @property-read int $id
 * @property int $agent_id
 * @property string $token
 * @property bool $revoked
 * @property Carbon|null $revoked_at
 *
 * @property-read Agent $agent
 */
class AgentToken extends Model {

  protected $fillable = ['agent_id', 'token'];
  protected $visible = [];

  protected $casts = [
    'revoked'    => 'bool',
    'revoked_at' => 'datetime:Y-m-d H:i:s',
    'created_at' => 'datetime:Y-m-d H:i:s',
  ];

  // Helpers

  static public function createNew(Agent $agent): ?self
  {
    $token = Str::random(32);
    $agent_id = $agent->id;
    $model = new static(compact('agent_id', 'token'));
    $model->save();
    return $model->fresh();
  }

  public function revoke()
  {
    $this->revoked = true;
    $this->revoked_at = now();
    $this->save();
    $this->agent->fresh('tokens');
  }

  // Relations

  public function agent()
  {
    return $this->belongsTo(Agent::class);
  }
}
