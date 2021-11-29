<?php

namespace App\Models;

use App\Contracts\MustFillDetails;
use App\Notifications\InviteUserNotification;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Notifications\JoinHotelRequestNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\AsIntCollection;
use App\Support\IntCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use App\Lib\Cultuzz;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens as HasSanctumTokens;

/**
 * Class User
 * @package App\Models
 *
 * @property int $id
 * @property string $email
 * @property Carbon $email_verified_at
 * @property Carbon $pd_filled_at
 * @property Carbon $cd_filled_at
 * @property Carbon $setup_at
 * @property int $setup_step
 * @property bool $admin
 * @property int|null $agent_id
 * @property int|null $agent_user_id
 * @property string $lang
 *
 * @property-read string $fullName
 * @property-read bool $emailVerified
 * @property-read bool $pdFilled
 * @property-read bool $cdFilled
 * @property-read bool $setupComplete
 * @property-read Carbon $created_at
 *
 * @property-read UserProfile|null $profile
 * @property-read UserImage|null $image
 * @property-read string|null $avatar
 *
 * @property-read DBCollection|Group[] $groups
 * @property-read DBCollection|Page[] $pages
 *
 * @property-read DBCollection|Hotel[] $hotels
 * @property-read DBCollection|Hotel[] $createdHotels
 * @property-read DBCollection|Role[] $roles
 * @property-read DBCollection|Role[] $rolesWithPages
 * @property-read DBCollection|Role[] $applyingRoles
 * @property-read DBCollection|Role[] $createdRoles
 *
 * @property-read Agent|null $agent
 * @property-read DBCollection|OneTimeLoginLink[] $loginLinks
 * @property-read bool $ofAgent
 * @property-read DBCollection|Hotel[] $agentHotels
 */
class User extends Authenticatable implements MustVerifyEmail, MustFillDetails, CanResetPassword {

  use Notifiable, HasSanctumTokens, HasFactory;

  protected $fillable = [
    'email',
    'password',
    'tos_agreed',
    'lang',
  ];

  protected $hidden = [
    'password', 'remember_token', 'parents', 'agent_id', 'agent_user_id',
    'email_verified_at', 'pd_filled_at', 'cd_filled_at', 'setup_at',
    'created_at', 'updated_at', 'image',
  ];

  protected $casts = [
    'id'                    => 'int',
    'agent_id'              => 'int',
    'agent_user_id'         => 'int',
    'tos_agreed'            => 'boolean',
    'email_verified_at'     => 'datetime',
    'cd_filled_at'          => 'datetime',
    'pd_filled_at'          => 'datetime',
    'setup_at'              => 'datetime',
    'setup_step'            => 'int',
    'admin'                 => 'boolean',
    'parents'               => AsIntCollection::class,
  ];

  protected $appends = [
    'email_verified', 'pd_filled', 'cd_filled', 'setup_complete', 'hotels', 'avatar', 'of_agent'
  ];

  protected $with = ['profile', 'groups'];

  protected static function boot()
  {
    static::deleting(function (User $user) {
      optional($user->image)->delete();
      self::unguarded(fn () => self::where('parent', $user->id)
        ->update(['parent' => $user->parent]));

      if ($user->groups->isNotEmpty()) {
        // $parent = $user->parents->last();
        $user->createdHotels->each(function (Hotel $h) use ($user) {
          $g = $user->groups->firstWhere('id', $h->group_id);
          $p = optional($g)->pivot->parents->last();
          $h->user_id = $p;
          $h->save();
        });
        $user->createdRoles->each(function (Role $r) use ($user) {
          $g = $user->groups()->firstWhere('id', $r->group_id);
          $p = optional($g)->pivot->parents->last();
          $r->user_id = $p;
          $r->save();
        });
        $user->groups->each(function (Group $group) use ($user) {
          $user->subordinates($group)->each(function (User $s) use ($user, $group) {
            $s->groups()->updateExistingPivot(optional($group)->id, [
              'parents' => $s->parents($group)->remove($user->id),
            ]);
          });
        });

      }
    });

    parent::boot();
  }

  public function sendPasswordResetNotification($token)
  {
    $this->notify(new ResetPasswordNotification($token, Group::getCurrent()));
  }

  public function sendEmailVerificationNotification($group = false)
  {
    $this->notify(new VerifyEmailNotification($group !== false ? $group : Group::getCurrent()));
  }

  public function sendInviteNotification($password, ?Group $group)
  {
    $this->notify(new InviteUserNotification($password, $group));
  }

  public function sendJoinHotelRequestNotification(Hotel $hotel)
  {
    $this->notify(new JoinHotelRequestNotification($hotel));
  }

  public function hasDetailsFilled()
  {
    return !is_null($this->pd_filled_at) && !is_null($this->cd_filled_at);
  }

  public function hasPersonalDetailsFilled()
  {
    return !is_null($this->pd_filled_at);
  }

  public function updateProperty($propertyDetails)
  {
    $this->profile->update($propertyDetails);

    return $this;
  }

  public function updateAddress($contactDetails)
  {
    $this->profile->update($contactDetails);

    return $this;
  }

  public function updatePersonalDetails($personalDetails)
  {
    if (is_null($this->profile)) {
      $profile = new UserProfile($personalDetails);
      $profile->user_id = $this->id;
      $profile->save();
    } else {
      $this->profile->update($personalDetails);
    }
    $this->pd_filled_at = $this->freshTimestamp();
    $this->save();
  }

  public function updateSetupStep($step)
  {
    if ($step > 5 || $step < 0) {
      $this->setup_at = $this->freshTimestamp();
    }
    if ($step >= 0) {
      // continue setup
      $this->setup_step = $step;
    }
    $this->save();
  }

  /**
   * @param array $attributes
   * @param Group $group Parent default group
   *
   * @return self
   */
  public static function createNew(array $attributes, ?Group $group = null): self
  {
    $attributes['password'] = Hash::make($attributes['password']);
    /** @var self $user */
    $user = static::create($attributes);
    /** @var self $parent */
    if ($parent = Arr::get($attributes, 'parent')) {
      $now = Carbon::now();
      $go = Arr::get($attributes, 'group_id');
      $group_id = $go ?: optional($group)->id;

      $parents = $go ? null : $parent->parents(Group::find($group_id))->add($parent->id);

      $attrs = [
        'parent'            => $parent->id,
        'tos_agreed'        => false,
        'email_verified_at' => $now,
        'pd_filled_at'      => $now,
        'cd_filled_at'      => $now,
        'setup_at'          => $now,
      ];
      self::unguarded(fn () => $user->update($attrs));
    }
    $user->roles()->attach(Arr::get($attributes, 'roles', []));
    $user->groups()->attach($group_id, [
      "all_group_hotels" => Arr::get($attributes, 'all_group_hotels', false),
      "parents" => $parents
    ]);
    if (!!$go) Group::whereId($group_id)->update(["group_owner" => $user->id]);
    $user->profile()->create(Arr::get($attributes, 'profile'));
    $pages = collect(Arr::get($attributes, 'apages', []))
      ->merge(Arr::get($attributes, 'pages', []));
    $user->pages()->attach(Page::idsByNames($pages));

    if ($avatar = Arr::get($attributes, 'avatar.upload')) {
      // create new image
      UserImage::create($avatar, $user);
    }
    return $user->fresh('profile', 'image');
  }

  /**
   * @param array $attributes
   *
   * @return self
   */
  public static function createNewFromAgent(Agent $agent, array $attributes): self
  {
    $attributes['password'] = Hash::make($attributes['password']);
    /** @var self $user */
    $user = static::create($attributes);
    $now = Carbon::now();
    $attrs = [
      // 'parents'           => null,
      'tos_agreed'        => false,
      'email_verified_at' => $now,
      'pd_filled_at'      => $now,
      'cd_filled_at'      => $now,
//      'setup_at'          => $now,
      'agent_id'          => $agent->id,
      'agent_user_id'     => Arr::get($attributes, 'id'),
    ];
    self::unguarded(fn () => $user->update($attrs));
    $user->profile()->create(Arr::get($attributes, 'profile'));
    $user->groups()->attach($agent->group_id, [
      "all_group_hotels" => Arr::get($attributes, 'all_group_hotels', false)
    ]);
    if ($avatar = Arr::get($attributes, 'avatar.upload')) {
      // create new image
      UserImage::create($avatar, $user);
    }
    return $user->fresh('profile', 'image');
  }

  /**
   * @param array $attributes
   *
   * @return self
   */
  public function modify(array $attributes): self
  {
    if ($password = Arr::get($attributes, 'password')) {
      Arr::set($attributes, 'password', Hash::make($password));
    }
    $this->update($attributes);
    $this->profile()->update(Arr::get($attributes, 'profile'));

    /** @var Hotel $hotel */
    $hotel = Arr::get($attributes, 'hotel');
    if ($hotel) {
      $oldRoles = $this->rolesForHotel($hotel);
      $this->roles()->detach($oldRoles);
      $this->roles()->attach(Arr::get($attributes, 'roles', []));
    }

    $pages = collect(Arr::get($attributes, 'apages', []))
      ->merge(Arr::get($attributes, 'pages', []));
    $this->pages()->sync(Page::idsByNames($pages));

    if (Arr::get($attributes, 'avatar.remove', false)) {
      // remove existing image
      optional($this->image)->delete();
    }
    if ($avatar = Arr::get($attributes, 'avatar.upload')) {
      // create/replace image
      optional($this->image)->delete();
      UserImage::create($avatar, $this);
    }

    return $this->fresh('profile', 'image');
  }

  /**
   * @param array $attributes
   *
   * @return self
   */
  public function modifyFromAgent(array $attributes): self
  {
    if ($password = Arr::get($attributes, 'password')) {
      Arr::set($attributes, 'password', Hash::make($password));
    }
    $this->update($attributes);
    $this->profile()->update(Arr::get($attributes, 'profile'));

    if (Arr::get($attributes, 'avatar.remove', false)) {
      // remove existing image
      optional($this->image)->delete();
    }
    if ($avatar = Arr::get($attributes, 'avatar.upload')) {
      // create/replace image
      optional($this->image)->delete();
      UserImage::create($avatar, $this);
    }

    return $this->fresh('profile', 'image');
  }

  public function createOneTimeLoginLink($payload): ?OneTimeLoginLink
  {
    return OneTimeLoginLink::createNew($this, $payload);
  }

  /**
   * @param $group_id
   * @param integer|null $id
   *
   * @return DBCollection|self[]|self
   */
  public static function listWithRolesForGroup($group_id, $id = null)
  {
    $q = self::query()
                 ->whereHas('groups', fn($q) => $q->where('id', $group_id))
                 ->with([
                   'roles' => fn ($q) => $q->select('id', 'name')->where('group_id', $group_id)->without('pages'),
                   'all_roles' => fn ($q) => $q->select('id', 'name')->where('group_id', $group_id)->without('pages')
                  ]);

    if ($id) $q->where('id', $id);
    $users = $q->get()
               ->each(fn (User $u) => ($u->makeHidden(['hotels'])));

    return $id ? $users->first() : $users;
  }

  /**
   * @param Hotel|null $hotel
   * @param bool $single
   *
   * @return DBCollection|mixed
   */
  public static function listWithRolesForHotel(?Group $group, ?Hotel $hotel, User $root, $id = null)
  {
    $hid = $hotel->id ?? 0;
    $q = self::query()
             ->whereHas('groups', fn ($q) => $q->where('group_id', optional($group)->id)->whereJsonContains('parents', $root->id))
             ->whereHas('all_roles', fn ($q) => $q->where('hotel_id', $hid))
             ->with([
               'pages',
               'roles' => fn ($q) => $q->where('hotel_id', $hid)->select('id', 'name')->without('pages'),
               'all_roles' => fn ($q) => $q->where('hotel_id', $hid)->select('id', 'name', 'role_user.confirmed as confirmed')->without('pages')
             ]);

    if ($id) $q->where('id', $id);
    $users = $q->get()
               ->each(fn (User $u) => ($u->makeHidden(['hotels'])));

    return $id ? $users->first() : $users;
  }

  /**
   * Get all users
   *
   * @param User $user | Current user
   * @param bool $include | Current user include or not
   */
  public static function allWithRoles(User $user, $include = false)
  {
    $q = self::query()
             ->with([
               'pages',
               'roles' => fn ($q) => $q->select('id', 'name')->without('pages'),
             ]);

    if (!$include) $q->whereNotIn('id', [$user->id]);

    return $q->get()->each(fn (User $u) => ($u->makeHidden(['hotels', 'group'])));
  }

  /*
   * Create user through a
   * link
   *
   * @param User $parent
   * @param array $data
   * @param Group $group Parent default group
   *
   * @return User
   */
  public static function createUserInvite(User $parent, $data, ?Group $group): ?self
  {
    $password = Cultuzz::generatePassword();
    $now = Carbon::now();
    $group_id = $parent->admin ? Arr::get($data, 'group_id') : optional($group)->id;
    $attributes = [
      'email'             => $data['email'],
      'password'          => Hash::make($password),
      'parent'            => $parent->id,
      'lang'              => app()->getLocale(),
      'tos_agreed'        => false,
      'email_verified_at' => $now,
      'cd_filled_at'      => $now,
      'setup_at'          => $now,
    ];
    /** @var self $user */
    $user = static::unguarded(fn () => static::create($attributes));
    $user->roles()->attach(Arr::get($data, 'roles', []));
    $group = Group::find($group_id);
    $user->groups()->attach($group_id, [
      "all_group_hotels" => Arr::get($attributes, 'all_group_hotels', false),
      'parents'          => $parent->parents($group)->add($parent->id)
    ]);

    if ($parent->admin) {
      Group::whereId($group_id)->update(["group_owner" => $user->id]);
    }
    $user->sendInviteNotification($password, $group);
    return $user->fresh();
  }

  /**
   * Allow user to access
   * Property
   *
   * @param Hotel $hotel
   * @param User $parent
   * @param Group $group | null
   * @param array $data
   *
   * @return self
   */
  public static function addHotelAccessToUser(Hotel $hotel, User $parent, ?Group $group, array $data)
  {
    $user = self::firstWhere('email', $data['email']);

    $parents = array_values(
      $user->parents($group)
      ->merge($parent->parents($group)->add($parent->id))
      ->unique()->all()
    );
    $user->save();
    $roles = Arr::get($data, 'roles', []);
    $user->roles()->attach($roles, ['confirmed' => false]);

    if ($user->groups->contains(optional($group)->id)) {
      $user->groups()->updateExistingPivot($group->id, ['parents' => $parents]);
    } else {
      $user->groups()->attach(optional($group)->id, ['parents' => $parents]);
    }
    $user->sendJoinHotelRequestNotification($hotel);

    return $user->fresh();
  }

  /**
   * Confirm role given to user
   * for a hotel
   *
   * @param Hotel $hotel
   * @param User $user
   *
   * @return User
   */
  public static function confirmHotelRolesForUser(Hotel $hotel, User $user): User
  {
    $roles = $user->all_roles
      ->where('hotel_id', $hotel->id)
      ->where('pivot.confirmed', 0);
    $roles->each(fn($role) => $user->all_roles()->updateExistingPivot($role->id, ['confirmed' => 1]));

    return $user->fresh();
  }

  /**
   * Remove access to Hotel
   * from User
   *
   * @return bool
   */
  public static function removeAccessToHotel(Hotel $hotel, User $user)
  {
    $group = $user->groups->firstWhere('id', $hotel->group_id);
    $uroles = $user->all_roles()
      ->where('group_id', optional($group)->id)
      ->where('hotel_id', $hotel->id)->get();
    $roleIds = $uroles->pluck('id');

    $user->all_roles()->detach($roleIds);
    if ($group) {
      $p = $group->pivot->parents->last();
      if ($user->ownsProperty($hotel)) {
        $hotel->user_id = $p;
        $hotel->save();
      }

      $user->createdRoles()->where('hotel_id', $hotel->id)->get()->each(function(Role $role) use ($p) {
        $role->user_id = $p;
        $role->save();
      });
    }

    $groles = $user->all_roles->where('group_id', optional($group)->id);
    if ($group && $groles->isEmpty()) {
      $user->subordinates($group)->each(function (User $s) use ($user, $group) {
        $s->groups()->updateExistingPivot($group->id, [
          'parents' => $s->parents($group)->remove($user->id),
        ]);
      });
      $user->groups()->detach($group->id);
    }

    if ($user->all_roles->isEmpty() && $user->pages->isEmpty()) $user->delete();
    return true;
  }

  /**
   * Check if user has
   * one of the role
   * for given role ids
   *
   * @param User $user
   * @param array $role_ids
   *
   * @return bool
   */
  public static function hasAlreadyRoles(User $user, array $role_ids)
  {
    $exists = $user->all_roles->whereIn('id', $role_ids);
    return $exists->isNotEmpty();
  }

  function subordinates(?Group $group = null, $includeSelf = false): Collection
  {
    $q = self::query()
      ->whereHas('groups', function ($q) use ($group) {
        if ($group) $q->where('id', $group->id);
        $q->whereJsonContains('parents', $this->id);
      });
    if ($includeSelf) {
      $q->orWhere('id', $this->id);
    }

    return $q->get();
  }

  /**
   * Get user subordinates' ids
   * for a group or for all groups (when $group = null)
   *
   * @param Group $group
   * @param bool $includeSelf
   *
   * @return array
   */
  function subordinateIds(?Group $group = null, $includeSelf = false): Collection
  {
    $q = self::query()
      ->whereHas('groups', function ($q) use ($group) {
        if ($group) $q->where('id', $group->id);
        $q->whereJsonContains('parents', $this->id);
      });
    if ($includeSelf) {
      $q->orWhere('id', $this->id);
    }

    return $q->setEagerLoads([])->get(['id'])->pluck('id');
  }

  function isSubordinateOf($id, Group $group): bool
  {
    return self::query()
      ->whereHas('groups', fn ($q) => $q->where('id', $group->id)->whereJsonContains('parents', $id))
      ->where('id', $this->id)->exists();
  }

  function isRootOf($id, Group $group): bool
  {
    return self::query()
      ->whereHas('groups', fn ($q) => $q->where('id', $group->id)->whereJsonContains('parents', $this->id))
      ->where('id', $id)->exists();
  }

  function rolesForHotel(?Hotel $hotel): Collection
  {
    if (!$hotel) return collect([]);
    return $this->roles->where('hotel_id', $hotel->id);
  }

  function pagesForHotel(?Hotel $hotel): Collection
  {
    if ($this->admin) return Page::allHotelNames();
    $group = $this->ofAgent ? $this->agent->group : $this->groups->firstWhere('id', $hotel->group_id);
    $roles = $this->rolesForHotel($hotel)->reject(fn (Role $r) => $r->inherit_from_user);
    $roles_pages = $roles->flatMap->pages->pluck('name');

    if (!$group) {
      if ($this->ownsProperty($hotel) || ($this->ofAgent && $this->hasAccessToHotel($hotel))) {
        return Page::allHotelNames();
      }
      return collect();
    }

    $g = $group->pages
      ->filter(fn (Page $p) => $p->for_hotel)
      ->pluck('name');
    if ($this->subordinateIds($group, true)->contains($hotel->user_id) || $this->ofAgent) {
      return $g;
    }

    if ($group->pivot->all_group_hotels && $roles->isEmpty()) {
      return $this->pages->filter(fn (Page $p) => $p->for_hotel)->pluck('name')->intersect($g);
    }
    return $roles_pages->intersect($g);
  }

  function adminPagesForGroup(?Group $group = null): Collection
  {
    if ($this->ofAgent) return collect();

    $group = $this->admin ? $group : $this->groups->firstWhere('id', optional($group)->id);
    if (!$group) {
      return Page::allUserNames(true);
    }
    $gpages = $group->pages->reject(fn (Page $p) => $p->for_hotel);
    if ($this->admin || $this->ownsGroup($group)) {
      if ($group->agent) {
        $gpages = $gpages->filter(fn (Page $p) => in_array($p->name, Page::AGENT_ADMIN_PAGES));
      }
      return $gpages->pluck('name');
    }
    return $gpages->filter(fn (Page $p) => in_array($p->name, Page::GROUP_PROTECTED_PAGES));
    // return $this->pages->reject(fn (Page $p) => $p->for_hotel)->pluck('name');
  }

  function hasAccessToHotel(Hotel $hotel): bool
  {
    if ($this->ofAgent) {
      return $this->agentHotels()->where('id', $hotel->id)->exists();
    }
    return $this->hotels->contains('id', $hotel->id);
  }

  function setUserPages(?Group $group)
  {
    $this->perms = $this->adminPagesForGroup($group);
  }

  /**
   * Check if user belongs to
   * a given group or has no group
   *
   * @param Group $group
   * @return bool
   */
  public function belongsToGroup(?Group $group = null): bool
  {
    return $this->groups->firstWhere('id', optional($group)->id) ? true : false;
  }

  /**
   * Check if user owns
   * the given group
   *
   * @param Group $group
   * @return bool
   */
  public function ownsGroup(Group $group)
  {
    return $this->groups
      ->where('id', optional($group)->id)
      ->where('group_owner', $this->id)
      ->first() ? true : false;
  }

  /**
   * Check if user owns
   * the given hotel
   *
   * @param Hotel $hotel
   * @return bool
   */
  public function ownsProperty(?Hotel $hotel)
  {
    return optional($hotel)->user_id === $this->id;
  }

//  function setHotelsPages()
//  {
//    $this->hotels->each(function (Hotel $hotel) {
//      $hotel->perms = $this->pagesForHotel($hotel);
//      logger()->info($hotel->perms);
//    });
//  }

  // Attributes

  public function getHotelsAttribute()
  {
    if ($this->admin) {
      $hotels = Hotel::all();
    } elseif ($this->ofAgent) {
      $hotels = $this->agentHotels;
    } else {
      $user_created_hotels = $this->createdHotels;
      $hotels_from_groups = collect([]);
      $this->groups->each(function (Group $group) use (&$hotels_from_groups) {
        if (($group->group_owner == $this->id) || $group->pivot->all_group_hotels) {
          $hotels_from_groups = $hotels_from_groups->merge($group->hotels);
        }
      });
      $hotels_from_roles = $this->applyingRoles->pluck('hotel')
          ->merge(Hotel::query()->whereIn('group_id', $this->groups->pluck('id'))
            ->whereIn('user_id', $this->subordinateIds(null, true)
          )
          ->get());

      $hotels = $user_created_hotels->merge($hotels_from_groups)
        ->merge($hotels_from_roles)
        ->unique('id')->values();
    }

    $hotels->each(fn (Hotel $hotel) => ($hotel->perms = $this->pagesForHotel($hotel)));
    return $hotels;
  }

  function getFullNameAttribute()
  {
    if (!$this->hasPersonalDetailsFilled()) {
      return null;
    }
    return $this->profile->first_name.' '.$this->profile->last_name;
  }

  function getEmailVerifiedAttribute()
  {
    return !is_null($this->email_verified_at);
  }

  function getPdFilledAttribute()
  {
    return !is_null($this->pd_filled_at);
  }

  function getCdFilledAttribute()
  {
    return !is_null($this->cd_filled_at);
  }

  function getSetupCompleteAttribute()
  {
    return !is_null($this->setup_at);
  }

  function getAvatarAttribute()
  {
    return $this->image->url ?? null;
  }

  function getOfAgentAttribute()
  {
    return isset($this->agent_id);
  }

  public function parents(Group $group = null)
  {
    $g = $this->groups->firstWhere('id', optional($group)->id);
    if (!$g) return new IntCollection([]);

    return optional($g->pivot)->parents;
  }

  /**
   * Get user's primary group
   *
   * @return Group
   */
  public function primaryGroup()
  {
    return $this->groups->first();
  }

  // Relations

  function profile()
  {
    return $this->hasOne(UserProfile::class);
  }

  function image()
  {
    return $this->hasOne(UserImage::class);
  }

  /* function group()
  {
    return $this->belongsTo(Group::class);
  } */

  function groups()
  {
    return $this->belongsToMany(Group::class)->withPivot('all_group_hotels', 'parents')
      ->using(GroupUser::class);
  }

  function pages()
  {
    return $this->belongsToMany(Page::class);
  }

  /**
   * Returns confirmed user roles
   * for <b>ALL</b> hotels
   */
  function roles()
  {
    return $this->belongsToMany(Role::class)->wherePivot('confirmed', true);
  }

  /**
   * Returns user roles
   * for <b>ALL</b> hotels
   */
  function all_roles()
  {
    return $this->belongsToMany(Role::class)->withPivot('confirmed');
  }

  /**
   * Returns user roles with pages for <b>ALL</b> hotels
   */
  function rolesWithPages()
  {
    return $this->roles()->whereHas('pages');
  }

  /**
   * Returns user roles with pages for <b>ALL</b> hotels
   */
  function applyingRoles()
  {
    return $this->roles()->where(function ($q) {
      $q->where('inherit_from_user', true)->orWhereHas('pages');
    });
  }

  /**
   * Returns roles created by user
   */
  function createdRoles()
  {
    return $this->hasMany(Role::class);
  }

  /**
   * Returns hotels created by user
   */
  function createdHotels()
  {
    return $this->hasMany(Hotel::class);
  }

  function agent()
  {
    return $this->belongsTo(Agent::class);
  }

  /**
   * Returns hotels assigned to, or created by user (AGENTS ONLY)
   */
  function agentHotels()
  {
    return $this->belongsToMany(Hotel::class, 'agent_user_hotel');
  }

  /**
   * Returns hotel groups created by user
   */
  function createdGroups()
  {
    return $this->hasMany(Group::class);
  }

  function emailChanges()
  {
    return $this->hasMany(EmailChange::class);
  }

  public function dashboardWidgetSettings()
  {
    return $this->hasMany(DashboardWidgetSettings::class);
  }

}
