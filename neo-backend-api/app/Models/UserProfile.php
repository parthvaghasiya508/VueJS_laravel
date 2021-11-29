<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserProfile
 * @package App\Models
 *
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $tel
 *
 * @property-read User $user
 */
class UserProfile extends Model {

  protected $primaryKey = 'user_id';
  protected $fillable = [
    'first_name', 'last_name', 'tel', 'date_format', 'number_format', 'default_language'
  ];
  protected $hidden = [
    'user_id', 'created_at', 'updated_at',
  ];

  protected $appends = ['name'];

  // Attributes

  function getNameAttribute()
  {
    if (isset($this->first_name, $this->last_name)) {
      // FIXME: locale support?
      return "{$this->first_name} {$this->last_name}";
    }
    return '';
  }

  // Relations

  function user()
  {
    return $this->belongsTo(User::class);
  }
}
