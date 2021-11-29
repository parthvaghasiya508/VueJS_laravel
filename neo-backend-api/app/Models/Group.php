<?php

namespace App\Models;

use App\Support\Domain;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Class Group
 * @package App\Models
 *
 * @property-read int $id
 * @property string $name
 * @property string|null $e_domain
 * @property string|null $b_domain
 * @property bool $domains_locked
 * @property int $domains_status
 * @property array $style
 * @property array|null $config
 * @property int $user_id
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 *
 * @property-read GroupImage|null $image
 * @property-read string|null $logo
 *
 * @property-read string[] $adminPermissions
 *
 * @property-read DBCollection|Hotel[] $hotels
 * @property-read DBCollection|User[] $users
 * @property-read DBCollection|Role[] $roles
 * @property-read DBCollection|Page[] $pages
 * @property-read User|null $owner
 * @property-read Agent|null $agent
 * @property-read string $effectiveExtranetDomain
 * @property-read string $effectiveBookingDomain
 */
class Group extends Model {

  use HasFactory;

  protected $fillable = ['name', 'b_domain', 'e_domain', 'style', 'config'];
  protected $appends = ['logo'];
  protected $hidden = ['image', 'updated_at', 'pages', 'hotels'];
  protected $withCount = ['hotels', 'users'];
  protected $with = ['hotels'];
  protected $casts = [
    'style'          => 'array',
    'config'         => 'array',
    'domains_status' => 'int',
    'domains_locked' => 'boolean',
  ];

  const DOMAIN_STATUS_NONE = 0;
  const DOMAIN_STATUS_INVALID = 1;
  const DOMAIN_STATUS_OK = 2;

  protected static function boot()
  {
    static::deleting(function (Group $group) {
      optional($group->image)->delete();
    });

    parent::boot();
  }

  /**
   * @param $domain
   *
   * @return Model|self|null
   */

  public static function findByLockedDomain($domain)
  {
    return self::query()->firstWhere(['e_domain' => $domain, 'domains_locked' => true]);
  }

  /**
   * @return self|null
   */
  public static function getCurrent()
  {
    return self::findByLockedDomain(config('app.domain'));
  }

  /** @return self */

  public static function create(array $data, User $user)
  {
    $model = new static($data);
    $model->user_id = $user->id;
    $model->save();
    if ($logo = Arr::get($data, 'logo.upload')) {
      // create new group image
      GroupImage::create($logo, $user, $model);
    }
    $model->pages()->attach(Page::idsByNames(Arr::get($data, 'pages', [])));
    return $model->fresh('image');
  }

  /** @return self */
  public function modify(array $data, User $user)
  {
    $this->update($data);
    if (Arr::get($data, 'logo.remove', false)) {
      // remove existing image
      optional($this->image)->delete();
    }
    if ($logo = Arr::get($data, 'logo.upload')) {
      // create new image
      optional($this->image)->delete();
      GroupImage::create($logo, $user, $this);
    }
    if ($user->admin) {
      $this->pages()->sync(Page::idsByNames(Arr::get($data, 'pages', [])));
    }
    return $this->fresh('image');
  }

  /** @return self */
  public function withData($owner = true, $pages = true): self
  {
    if ($pages) {
      $this->loadMissing('pages')->makeVisible('pages');
    }
    if ($owner) {
      optional($this->owner)->makeHidden(['hotels', 'groups']);
    }
    return $this;
  }

  // Attributes

  public function getLogoAttribute()
  {
    return $this->image->url ?? null;
  }

  public function setBDomainAttribute($b_domain)
  {
    if (isset($this->attributes['b_domain']) && $this->domains_status !== self::DOMAIN_STATUS_NONE
      && $b_domain !== $this->attributes['b_domain']) {
      $this->domains_status = self::DOMAIN_STATUS_NONE;
    }
    $this->attributes['b_domain'] = $b_domain;
  }

  public function setEDomainAttribute($e_domain)
  {
    if (isset($this->attributes['e_domain']) && $this->domains_status !== self::DOMAIN_STATUS_NONE
      && $e_domain !== $this->attributes['e_domain']) {
      $this->domains_status = self::DOMAIN_STATUS_NONE;
    }
    $this->attributes['e_domain'] = $e_domain;
  }

  public function getEffectiveExtranetDomainAttribute()
  {
    return $this->domains_status === self::DOMAIN_STATUS_OK && $this->domains_locked
      ? $this->e_domain : Domain::getExtranetDomain();
  }

  public function getEffectiveBookingDomainAttribute()
  {
    return $this->domains_status === self::DOMAIN_STATUS_OK && $this->domains_locked && $this->b_domain
      ? $this->b_domain : Domain::getBookingDomain();
  }

  // Relations

  public function image()
  {
    return $this->hasOne(GroupImage::class);
  }

  public function hotels()
  {
    return $this->hasMany(Hotel::class);
  }

  public function users()
  {
    return $this->belongsToMany(User::class)->withPivot('all_group_hotels')
      ->using(GroupUser::class);
  }

  public function pages()
  {
    return $this->belongsToMany(Page::class);
  }

  public function roles()
  {
    return $this->hasMany(Role::class);
  }

  public function owner()
  {
    return $this->belongsTo(User::class, 'group_owner', 'id');
  }

  public function agent()
  {
    return $this->hasOne(Agent::class);
  }
}
