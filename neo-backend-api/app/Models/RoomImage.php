<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class RoomImage
 * @package App\Models
 *
 * @property-read int $id
 * @property string $room_id
 * @property int $image_id
 * @property int $display_order
 *
 * @property-read Image $image
 */
class RoomImage extends Model {

  protected $fillable = ['room_id', 'image_id', 'display_order'];
  protected $hidden = ['id'];
  public $timestamps = false;

  static function createNew($image_id, $room_id)
  {
    $display_order = (static::query()->where('room_id', $room_id)->max('display_order') ?? 0) + 1;
    $model = new static(compact('image_id', 'room_id', 'display_order'));
    $model->save();
    return $model->fresh(['image']);
  }

  /**
   * @param string $room_id
   * @param bool $idsOnly
   *
   * @return DBCollection|Collection|RoomImage[]
   */
  static function imagesForRoom($room_id, $idsOnly = true)
  {
    $q = static::query()->where('room_id', $room_id)->orderBy('display_order');
    if (!$idsOnly) {
      $q->with(['image']);
    }
    $r = $q->get();
    return $idsOnly ? $r->pluck('image_id') : $r;
  }

  // Relations

  public function image()
  {
    return $this->belongsTo(Image::class);
  }

}
