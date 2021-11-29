<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Currency
 * @package App\Models
 *
 * @property-read int $id
 * @property string $name
 * @property string $code
 * @property string $symbol
 */
class Currency extends Model {

  public $timestamps = false;
  protected $fillable = ['name', 'code', 'symbol'];
  protected $hidden = ['id', 'name'];

  /**
   * @param string $code
   *
   * @return static|Model|null
   */
  static function findByCode(string $code): ?Currency
  {
    return static::query()->firstWhere('code', $code);
  }

  /**
   * @param mixed $idOrCode
   *
   * @return static|Model|null
   */
  static function normalize($idOrCode): ?Currency
  {
    if (!$idOrCode) return null;
    if ($idOrCode instanceof static) return $idOrCode;
    $_ = is_numeric($idOrCode) ? 'id' : 'code';
    return static::query()->firstWhere($_, $idOrCode);
  }
}
