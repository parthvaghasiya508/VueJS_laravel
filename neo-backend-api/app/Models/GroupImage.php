<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as ImageFacade;

/**
 * Class GroupImage
 * @package App\Models
 *
 * @property-read int $id
 * @property int $user_id
 * @property string $group_id
 * @property string $code
 * @property bool $resized
 * @property int $width
 * @property int $height
 * @property int $size
 * @property int $orig_width
 * @property int $orig_height
 * @property int $orig_size
 * @property string $mime
 * @property-read Carbon $created_at
 *
 * @property-read User $user
 * @property-read Group $group
 * @property-read string $filename
 * @property-read string $originalFilename
 * @property-read string $url
 */
class GroupImage extends Model {

  const WIDTH = 200;
  const HEIGHT = 40;

  protected $casts = ['resized' => 'boolean', 'created_at' => 'datetime'];
  protected $hidden = ['user_id', 'updated_at', 'group_id', 'code'];
  protected $appends = ['url'];
  protected $fillable = ['group_id', 'code', 'user_id'];

  static function disk()
  {
    return Storage::disk('groups');
  }

  protected static function boot()
  {
    static::deleting(function (GroupImage $image) {
      $image->deleteFiles();
    });

    parent::boot();
  }

  static function create(UploadedFile $file, User $user, Group $group)
  {
    $disk = self::disk();
    // ensure that hotel group folder exists
    $group_id = $group->id;
    $user_id = $user->id;
    $code = Str::random(20);

    $model = new static(compact('group_id', 'user_id', 'code'));
    $tempName = $code.'_';
    if (!$disk->putFileAs('', $file, $tempName)) {
      return false;
    }
    // convert image to JPEG and generate result image
    $img = ImageFacade::make($disk->path($tempName));
    $img->orientate();
    $model->mime = $img->mime();
    $model->orig_width = $img->getWidth();
    $model->orig_height = $img->getHeight();
    $model->resized = $model->orig_height > self::HEIGHT || $model->orig_width > self::WIDTH;
    $img->save($disk->path($model->originalFilename), 90, $img->mime());
    $model->orig_size = $disk->size($model->originalFilename);
    $img->resize(self::WIDTH, self::HEIGHT, function ($constraint) {
      $constraint->aspectRatio();
      $constraint->upsize();
    });
    $model->width = $img->getWidth();
    $model->height = $img->getHeight();
    $img->save($disk->path($model->filename), 90, $img->mime());
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
      'Content-Type' => $this->mime,
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

  public function group()
  {
    return $this->belongsTo(Group::class);
  }

}
