<?php

namespace App\Models;

use App\Managers\PMSManager;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Cashier\Billable;

/**
 * Class Hotel
 * @package App\Models
 *
 * @property-read int $id
 * @property string $ctx
 * @property string $name
 * @property int|null $user_id
 * @property int|null $group_id
 * @property int|null $agent_id
 * @property Carbon|null $agent_setup_at
 * @property int $agent_setup_step
 * @property string|null country
 * @property bool $active
 * @property-read Carbon $created_at
 *
 * @property-read User $user
 * @property DBCollection|Role[] $roles
 * @property Group|null $group
 *
 * @property-read HotelImage|null $image
 * @property-read DBCollection|Image[] $images
 * @property-read DBCollection|Image[] $imagesWithRooms
 * @property-read string|null $logo
 *
 * @property-read DBCollection|User[] $agentUsers
 */
class Hotel extends Model
{
  use Billable, HasFactory;

  public $incrementing = false;
  protected $fillable = [
    'id',
    'ctx',
    'name',
    'user_id',
    'city',
    'country',
    'active',
    'group_id',
    'agent_id',
    'roomdb_id',
    'roomdb_is_master',
  ];
  protected $hidden = ['user_id', 'ctx', 'image', 'updated_at'];
  protected $appends = ['logo', 'agent_setup_complete'];
  protected $casts = [
    'id'               => 'int',
    'active'           => 'bool',
    'agent_setup_at'   => 'datetime',
    'agent_setup_step' => 'int',
    'roomdb_is_master' => 'bool',
  ];
  protected $attributes = [
    'active' => false,
  ];

  const CAPACITY_ROOMS = 0;
  const CAPACITY_BEDS = 1;

  protected static function boot ()
  {
    static::deleting(function (Hotel $hotel) {
      optional($hotel->roles())->delete();
    });

    parent::boot();
  }

  /**
   * @param array $data
   *
   * @return Hotel|Model|null
   */
  static function create(array $data)
  {
    $model = new static($data);
    $model->save();
    if (!self::createdByAgent($model)) $model->addAdminRole();

    return $model->fresh(['user', 'image']);
  }

  /**
   * @param array $data
   *
   * @return Hotel|Model|null
   */
  static function createEmpty(array $data)
  {
    $data += [
      'country'       => 'DE',
      'ctx'           => 'CLTZ',
      'name'          => '',
    ];
    return self::create($data);
  }

  // Helpers

  /**
   * Check if the hotel is created by an Agent
   */
  public static function createdByAgent(Hotel $hotel)
  {
    return $hotel->agent_id;
  }

  /**
   * Normalize Hotel instance(s)
   * to add missed data (Eg: currency_code)
   *
   * @return Collection|array
   */
  public static function normalize($instance)
  {
    $manager = app()->make(PMSManager::class);
    if ($instance instanceof Collection) {
      $collect = collect([]);
      foreach ($instance as $hotel) {
        $collect->push($manager->getHotel($hotel));
      }
      return $collect;
    }

    if ($instance instanceof Hotel) {
      return $manager->getHotel($instance);
    }
    return [];
  }

  public function updateAgentSetupStep($step)
  {
    if ($step > 5 || $step < 0) {
      $this->agent_setup_at = $this->freshTimestamp();
    }
    if ($step >= 0) {
      // continue setup
      $this->agent_setup_step = $step;
    }
    $this->save();
  }

  public function addAdminRole()
  {
      $pageIds = Page::where('for_hotel', true)->get()->pluck('id')->toArray();
      $admin = $this->user;

      $data = [
        'name'     => 'Admin',
        'user_id'  => $this->user_id,
        'hotel_id' => $this->id,
        'group_id' => $this->group_id,
      ];

      $role = Role::create($data);
      $role->pages()->sync($pageIds);
      $admin->roles()->attach($role->id);

      return $role;
  }

  function toggleActive($active)
  {
    $this->update(['active' => !!$active]);
  }

  /**
   * @param Collection|array|null $ids
   *
   * @return bool
   */
  function isHotelRoles($ids): bool
  {
    if (!$ids) {
      return true;
    }
    $roles = Role::query()->setEagerLoads([])->findMany($ids);
    return $roles->every(fn(Role $role) => $role->hotel_id == $this->id);
  }

  function setPagesForUser(User $user)
  {
    $this->perms = $user->pagesForHotel($this);
  }

  function withPagesForUser(User $user): self
  {
    return tap($this, fn(Hotel $h) => $h->setPagesForUser($user));
  }

  // Attributes

  function getLogoAttribute()
  {
    return $this->image->url ?? null;
  }

  function getAgentSetupCompleteAttribute()
  {
    return !is_null($this->agent_setup_at);
  }


  // Relations

  function user()
  {
    return $this->belongsTo(User::class);
  }

  function image()
  {
    return $this->hasOne(HotelImage::class);
  }

  function images()
  {
    return $this->hasMany(Image::class);
  }

  function imagesWithRooms()
  {
    return $this->images()->with('rooms');
  }

  public function group()
  {
    return $this->belongsTo(Group::class);
  }

  public function roles()
  {
    return $this->hasMany(Role::class);
  }

  public function agentUsers()
  {
    return $this->belongsToMany(User::class, 'agent_user_hotel');
  }
}
