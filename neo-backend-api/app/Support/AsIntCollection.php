<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

class AsIntCollection extends AsCollection {

  public static function castUsing(array $arguments)
  {
    return new class implements CastsAttributes {

      public function get($model, $key, $value, $attributes)
      {
        return (new IntCollection(json_decode($attributes[$key] ?? null, true)))->toInt();
      }

      public function set($model, $key, $value, $attributes)
      {
        return [$key => json_encode($value)];
      }
    };
  }

}
