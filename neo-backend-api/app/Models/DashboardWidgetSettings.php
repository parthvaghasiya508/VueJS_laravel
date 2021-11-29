<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class DashboardWidgetSettings
 * @package App\Models
 *
 * @property-read int $id
 * @property int $user_id
 * @property int $widget_group
 * @property int $widget_id
 * @property int $visible
 * @property string $position
 * @property-read Carbon $created_at
 */
class DashboardWidgetSettings extends Model {

  use HasFactory;

  protected $guarded = ['id'];
  protected $hidden = ['user_id', 'code'];
  protected $fillable = ['user_id', 'widget_group', 'widget_id', 'visible', 'position', 'x', 'y'];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
