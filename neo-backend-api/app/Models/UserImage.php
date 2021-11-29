<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as ImageFacade;

/**
 * Class UserImage
 * @package App\Models
 *
 * @property-read int $id
 * @property int $user_id
 * @property string $code
 * @property int $width
 * @property int $height
 * @property int $size
 * @property int $orig_width
 * @property int $orig_height
 * @property int $orig_size
 * @property-read Carbon $created_at
 *
 * @property-read User $user
 * @property-read string $filename
 * @property-read string $originalFilename
 * @property-read string $url
 */
class UserImage extends Model {

  const SIZE = 160;

  protected $casts = ['created_at' => 'datetime'];
  protected $hidden = ['user_id', 'updated_at', 'code'];
  protected $appends = ['url'];
  protected $fillable = ['code', 'user_id'];

  static function disk()
  {
    return Storage::disk('avatars');
  }

  protected static function boot()
  {
    static::deleting(function (UserImage $image) {
      $image->deleteFiles();
    });

    parent::boot();
  }

  static function create(UploadedFile $file, User $user)
  {
    $disk = self::disk();

    $user_id = $user->id;
    $code = Str::random(20);

    $model = new static(compact('user_id', 'code'));
    $tempName = $code.'_';
    if (!$disk->putFileAs('', $file, $tempName)) {
      return false;
    }

    // convert image to JPEG and generate result image
    $img = ImageFacade::make($disk->path($tempName));
    $img->orientate();
    $model->orig_width = $img->getWidth();
    $model->orig_height = $img->getHeight();
    $img->save($disk->path($model->originalFilename), 90, 'jpg');
    $model->orig_size = $disk->size($model->originalFilename);
    $img->fit(self::SIZE, self::SIZE, function ($constraint) {
      $constraint->upsize();
    });
    $model->width = $img->getWidth();
    $model->height = $img->getHeight();
    $img->save($disk->path($model->filename), 90, 'jpg');
    $model->size = $disk->size($model->filename);
    $disk->delete($tempName);
    $model->save();

    return $model->fresh();
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
    return $this->code;
  }

  public function getOriginalFilenameAttribute()
  {
    return $this->code.'_o';
  }

  // Relations

  public function user()
  {
    return $this->belongsTo(User::class);
  }

}
