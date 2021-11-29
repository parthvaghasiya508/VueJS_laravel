<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as ImageFacade;

/**
 * Class Image
 * @package App\Models
 *
 * @property-read int $id
 * @property int $user_id
 * @property string $hotel_id
 * @property string $code
 * @property string $name
 * @property string $display_name
 * @property bool $lowres
 * @property int $width
 * @property int $height
 * @property int $size
 * @property int $orig_width
 * @property int $orig_height
 * @property int $orig_size
 * @property-read Carbon $created_at
 *
 * @property-read DBCollection|RoomImage[] $rooms
 * @property-read User $user
 * @property-read string $filename
 * @property-read string $originalFilename
 * @property-read string $url
 */
class Image extends Model {

  const WIDTH = 890;
  const HEIGHT = 550;

  protected $casts = ['lowres' => 'boolean', 'created_at' => 'datetime'];
  protected $hidden = ['user_id', 'updated_at', 'hotel_id', 'code'];
  protected $appends = ['url'];
  protected $fillable = ['hotel_id', 'code', 'user_id'];

  static function disk()
  {
    return Storage::disk('images');
  }

  protected static function boot()
  {
    static::deleting(function (Image $image) {
      $image->deleteFiles();
    });

    parent::boot();
  }

  static function create(UploadedFile $file, User $user, Hotel $hotel, $room = null)
  {
    $disk = self::disk();
    // ensure that hotel folder exists
    $hotel_id = $hotel->id;
    if(!$disk->exists($hotel_id)) {
      $disk->makeDirectory($hotel_id);
    }

    $user_id = $user->id;
    $code = Str::random(16);

    $model = new static(compact('hotel_id', 'user_id', 'code'));
    $tempName = $code.'_';
    if(!$disk->putFileAs($hotel_id, $file, $tempName)) {
      return false;
    }
    $tempName = $hotel_id.DIRECTORY_SEPARATOR.$tempName;

    // convert image to JPEG and generate result image
    $img = ImageFacade::make($disk->path($tempName));
    $img->orientate();
    $model->orig_width = $img->getWidth();
    $model->orig_height = $img->getHeight();
    $model->lowres = $model->orig_width<self::WIDTH || $model->orig_height<self::HEIGHT;
    $img->save($disk->path($model->originalFilename), 90, 'jpg');
    $model->orig_size = $disk->size($model->originalFilename);
    $img->fit(self::WIDTH, self::HEIGHT);
    $model->width = $img->getWidth();
    $model->height = $img->getHeight();
    $img->save($disk->path($model->filename), 90, 'jpg');
    $model->size = $disk->size($model->filename);
    $disk->delete($tempName);
    $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $model->name = $name;
    $model->display_name = $name;
    $model->save();

    if(isset($room)) {
      $model->addToRoom($room);
    }

    return $model->fresh(['rooms']);
  }

  public function addToRoom($room_id)
  {
    RoomImage::createNew($this->id, $room_id);
  }

  public function deleteFiles()
  {
    self::disk()->delete($this->filename);
    self::disk()->delete($this->originalFilename);
  }

  public function serve()
  {
    $dt = (new Carbon($this->created_at))->setTimezone('GMT')->format('D, d M Y, H:i:s T');
    return self::disk()->response($this->filename, null, [
      'Last-Modified' => $dt,
    ]);
  }

  // Attributes

  public function getUrlAttribute()
  {
    return self::disk()->url($this->filename);
  }

  public function getFilenameAttribute()
  {
    return $this->hotel_id.DIRECTORY_SEPARATOR.$this->code;
  }

  public function getOriginalFilenameAttribute()
  {
    return $this->hotel_id.DIRECTORY_SEPARATOR.$this->code.'_o';
  }

  // Relations

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function rooms()
  {
    return $this->hasMany(RoomImage::class, 'image_id', 'id');
  }
}
