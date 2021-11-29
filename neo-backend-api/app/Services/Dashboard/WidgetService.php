<?php

namespace App\Services\Dashboard;

use App\Managers\CDManager;
use App\Models\DashboardWidgetSettings;
use Illuminate\Http\Request;

class WidgetService
{
  /**
   * @var CDManager
   */
  protected $cdManager;

  /**
   * @var Request
   */
  protected $request;

  /**
   * @param CDManager $cdManager
   * @param Request $request
   */
  public function __construct(
    CDManager $cdManager,
    Request $request
  ) {
    $this->cdManager  = $cdManager;
    $this->request    = $request;
  }

  /**
   * @return array
   */
  public function getWidgetSettingsByUser() :array {
    $cultuzzDashboardSettings = $this->cdManager->getSettings();
    $existingSettings = $this->saveIfMissing($cultuzzDashboardSettings);
    foreach ($cultuzzDashboardSettings as $key => $settings) {
      $foundWidgetKey = array_search($settings['id'], array_column($existingSettings, 'widget_id'));
      $cultuzzDashboardSettings[$key]['visible'] = $existingSettings[$foundWidgetKey]['visible'];
      $cultuzzDashboardSettings[$key]['x'] = $existingSettings[$foundWidgetKey]['x'];
      $cultuzzDashboardSettings[$key]['y'] = $existingSettings[$foundWidgetKey]['y'];
    }

    return $cultuzzDashboardSettings;
  }

  /**
   * @param $cultuzzDashboardSettings
   * @return array
   */
  protected function saveIfMissing($cultuzzDashboardSettings) :array {
    $return = [];
    $existingSettings = DashboardWidgetSettings::where('user_id','=',$this->request->user()->id)->get()->toArray();
    foreach ($cultuzzDashboardSettings as $settings) {
      if (!$existingSettings) {
        $dashboardSettings = new DashboardWidgetSettings();
        $position = explode("-", $settings['position']);
        $dashboardSettings->fill([
          'user_id'       => $this->request->user()->id,
          'widget_group'  => $settings['group']['id'],
          'widget_id'     => $settings['id'],
          'visible'       => $settings['visible'],
          'x'             => intval($position['1']) - 1,
          'y'             => intval($position['0']) - 1
        ]);
        $dashboardSettings->save();
        $return[] = $dashboardSettings->fresh()->toArray();
      }
    }

    return $return ? $return : $existingSettings;
  }

  /**
   * Update widget visibility
   */
  public function updateWidgetVisibility() {
    $dashboardSql = DashboardWidgetSettings::where('user_id', '=', $this->request->user()->id);
    if ($this->request['allGroup']) {
      $dashboardSql->where('widget_group', '=', $this->request['id']);
    } else {
      $dashboardSql->where('widget_id', '=', $this->request['id']);
    }
    $dashboardSql->update(['visible' => $this->request['visibleState']]);
  }

  /**
   * Update widget position
   */
  public function updateWidgetPosition() {
    foreach ($this->request->all() as $widget) {
      $existingSettings = DashboardWidgetSettings::where('user_id', '=', $this->request->user()->id)
        ->where('widget_id', '=', $widget['widgetId'])
        ->first();
      if ($existingSettings) {
        $existingSettings->x = $widget['x'];
        $existingSettings->y = $widget['y'];
        $existingSettings->save();
      }
    }
  }
}
