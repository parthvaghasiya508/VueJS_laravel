<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class Agent
 * @package App\Models
 *
 * @property-read int $id
 * @property string $title
 * @property string $name
 * @property int|null $group_id
 * @property bool $active
 *
 * @property-read Collection|AgentToken[] $tokens
 * @property-read Collection|AgentToken[] $activeTokens
 * @property-read Collection|User[] $users
 * @property-read Collection|Hotel[] $hotels
 * @property-read Group|null $group
 * @property-read bool $has_group
 */
class Agent extends Model {

  protected $casts = ['active' => 'bool'];
  protected $guarded = ['id', 'group_id'];
  protected $visible = ['id', 'title', 'name', 'active', 'has_group'];
  protected $appends = ['has_group'];

  // Helpers

  /**
   * @param $name
   *
   * @return static|null|Model
   */
  static public function findByName($name): ?self
  {
    return self::query()->firstWhere(compact('name'));
  }

  static public function createNew($title, $name, $active): ?self
  {
    $active = !!$active;
    $model = new static(compact('title', 'name', 'active'));
    $model->save();
    return $model->fresh();
  }

  public function issueToken()
  {
    $token = AgentToken::createNew($this);
    $this->fresh(['tokens']);
    return $token;
  }

  public function isValidToken($token)
  {
    return $this->tokens->contains(fn (AgentToken $at) => ($at->token === $token && !$at->revoked));
  }

  // Attributes

  public function getHasGroupAttribute()
  {
    return !!$this->group_id;
  }

  // Relations

  public function tokens()
  {
    return $this->hasMany(AgentToken::class);
  }

  public function activeTokens()
  {
    return $this->tokens()->where('revoked', false);
  }

  public function users()
  {
    return $this->hasMany(User::class);
  }

  public function hotels()
  {
    return $this->hasMany(Hotel::class);
  }

  public function group()
  {
    return $this->belongsTo(Group::class);
  }
}
