<?php

namespace App\Models;

use App\Support\Domain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class OneTimeLoginLink
 * @package App\Models
 *
 * @property-read int $id
 * @property int $user_id
 * @property string $uuid
 * @property Carbon $expire_at
 * @property Carbon|null $used_at
 * @property string $exit_url
 *
 * @property-read User $user
 * @property-read bool $isActive
 * @property-read bool $isExpired
 * @property-read bool $isUsed
 * @property-read string $loginLink
 */
class OneTimeLoginLink extends Model {

  protected $fillable = ['exit_url'];

  protected $casts = [
    'expired'   => 'bool',
    'expire_at' => 'datetime',
    'used'      => 'bool',
    'used_at'   => 'datetime',
  ];

  // Helpers

  static public function createNew(User $user, $attributes): ?self
  {
    $model = new static($attributes);
    $ttl = Arr::get($attributes, 'ttl', 60);
    $model->expire_at = now()->addSeconds($ttl);
    $model->user_id = $user->id;
    $model->uuid = Str::uuid()->toString();
    $model->save();
    return $model->fresh();
  }

  public function markUsed(): self
  {
    $this->used_at = now();
    $this->save();
    return $this;
  }

  /**
   * @param string $uuid
   *
   * @return static|null|Model
   */
  static public function findUnused(string $uuid): ?self
  {
    return self::query()
               ->where(compact('uuid'))
               ->whereNull('used_at')
//               ->where('expire_at', '>', DB::raw('NOW()'))
               ->first();
  }

  // Attributes

  public function getIsExpiredAttribute(): bool
  {
    return $this->expire_at->isBefore(now());
  }

  public function getIsUsedAttribute(): bool
  {
    return isset($this->used_at);
  }

  public function getIsActiveAttribute(): bool
  {
    return !$this->isExpired && !$this->isUsed;
  }

  public function getLoginLinkAttribute(): string
  {
    $domain = Domain::getExtranetDomain();
    if ($group = $this->user->agent->group) {
      $domain = $group->effectiveExtranetDomain;
    }
    return 'https://'.$domain.'/login/'.$this->uuid;
  }

  // Relations

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
