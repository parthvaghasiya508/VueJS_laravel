<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class LogHotelStatus
 * @package App\Models
 *
 * @property-read int $id
 * @property bool $status
 * @property-read Carbon $created_at
 *
 * @property-read User $user
 * @property-read Hotel $hotel
 */
class LogHotelStatus extends Model {

  protected $table = 'log_hotel_status';
  protected $casts = ['status' => 'bool'];
  protected $fillable = ['user_id', 'hotel_id', 'status'];
  protected $with = ['user'];

  /**
   * @param Hotel $hotel
   * @param User|null $user
   * @param bool $status
   *
   * @return self|null
   */
  static function make(Hotel $hotel, ?User $user, bool $status): ?self
  {
    $model = new static([
      'hotel_id' => $hotel->id,
      'user_id'  => $user->id ?? null,
      'status'   => $status,
    ]);
    $model->save();
    return $model->fresh();
  }

  /**
   * @param int $hotel_id
   *
   * @return Collection|self[]
   */
  static function findByHotel(int $hotel_id)
  {
    return static::query()->where(compact('hotel_id'))->orderByDesc('created_at')->get();
  }

  // Relations
  function user(): BelongsTo
  {
    return $this->belongsTo(User::class)->without('hotels');
  }

  function hotel(): BelongsTo
  {
    return $this->belongsTo(Hotel::class);
  }
}
