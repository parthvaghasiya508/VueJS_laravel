<?php

namespace App\Models;

use App\Support\AsIntCollection;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupUser extends Pivot
{
  protected $casts = [
    'parents' => AsIntCollection::class
  ];
}
