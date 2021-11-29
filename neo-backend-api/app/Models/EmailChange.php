<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EmailChange
 * @package App\Models
 *
 * @property-read int $id
 * @property string $email
 * @property string $token
 */
class EmailChange extends Model {
  protected $fillable = ['email', 'token'];

  // Relations

  function user()
  {
    return $this->belongsTo(User::class);
  }
}
