<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Role
 * @package App\Models
 * @property-read int $id
 * @property string $name
 * @property int $user_id
 * @property int $hotel_id
 * @property int $group_id
 * @property bool $inherit_from_user
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 *
 * @property Collection|User[] $users
 * @property Collection|Page[] $pages
 * @property-read Group $group
 * @property-read Hotel $hotel
 */
class Role extends Model {

  use HasFactory;

  protected $guarded = ["id"];

  protected $with = ['pages'];
  protected $hidden = ['pivot', 'created_at', 'updated_at'];
  protected $casts = [
    'inherit_from_user' => 'boolean',
  ];

  public static function createNew(array $data)
  {
    $model = new static($data);
    $model->save();
    $pageIds = Page::idsByNames(Arr::get($data, 'pages'));
    $model->pages()->sync($pageIds);
    return $model->fresh();
  }

  public function modify(array $data)
  {
    $this->update($data);
    $pageIds = Page::idsByNames(Arr::get($data, 'pages'));
    $this->pages()->sync($pageIds);
    return $this->fresh();
  }

  // Helpers

  static function listForHotel(Hotel $hotel, User $user)
  {
    $createds = static::query()
        ->withCount('users')
        ->with('hotel:id,name')
        ->where('hotel_id', $hotel->id)
        ->whereIn('user_id', $user->subordinateIds($hotel->group, true))
        ->get();

    $gottens = $user->roles->where('hotel_id', $hotel->id)->map(fn (Role $role) => $role->loadCount('users')->load('hotel:id,name'));

    return $createds->merge($gottens)->unique('id')->values();
  }

  // Relations

  public function pages()
  {
    return $this->belongsToMany(Page::class);
  }

  public function users()
  {
    return $this->belongsToMany(User::class);
  }

  public function hotel()
  {
    return $this->belongsTo(Hotel::class);
  }

  public function group()
  {
    return $this->belongsTo(Group::class);
  }
}
