<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class Page
 * @package App\Models
 *
 * @property-read int $id
 * @property string $name
 * @property string|null $category
 * @property bool $for_hotel
 */
class Page extends Model {

  use HasFactory;

  public $timestamps = false;
  protected $guarded = ['id'];
  protected $hidden = ['pivot'];
  protected $casts = [
    'for_hotel'     => 'boolean',
    'display_order' => 'int',
  ];

  private const GROUPLESS_USER_PAGES = [
    'hotels',
    'users',
    'profile',
  ];

  public const AGENT_ADMIN_PAGES = [
    'group',
  ];

  public const GROUP_PROTECTED_PAGES = [
    'group',
  ];

  /**
   * Local cache
   * TODO: remove if/when pages count will grow
   */
  static private ?Collection $allPages = null;

  /**
   * @return Collection|self[]
   */
  static public function allPages(): Collection
  {
    return self::$allPages ?? (self::$allPages = static::query()->orderBy('display_order')->get());
  }

  static function allHotelNames(): Collection
  {
    return self::allPages()->where('for_hotel', true)->pluck('name');
  }

  static function allUserNames($groupless = false): Collection
  {
    return self::allPages()->filter(fn (Page $p) => !$p->for_hotel && (!$groupless || in_array($p->name, self::GROUPLESS_USER_PAGES)))->pluck('name');
  }

  static function allNames(): Collection
  {
    return self::allPages()->pluck('name');
  }

  static function idsByNames($names): Collection
  {
    if (!$names) return collect([]);
    return self::allPages()->whereIn('name', $names)->pluck('id');
  }
}
