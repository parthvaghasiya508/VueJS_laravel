<?php

namespace App\Http\Controllers\Api;

use App\Services\Dashboard\WidgetService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Managers\CUManager;

class WidgetController extends Controller {

  /**
   * Get recent report
   *
   * @param CUManager $manager
   *
   * @return array|null
   */

  public function update(Request $request, CUManager $manager)
  {
    $update = $manager->updateWidgets($request);
    return $update;
  }

  /**
   * @param WidgetService $widgetService
   * @return bool|void
   */
  public function updateWidgetVisibility(
    WidgetService $widgetService
  ) {
    try {
      $widgetService->updateWidgetVisibility();
      return true;
    } catch (\Exception $e) {
      abort(500, $e->getMessage());
    }
  }

  /**
   * @param WidgetService $widgetService
   * @return bool|void
   */
  public function updateWidgetPosition(
    WidgetService $widgetService
  ) {
    try {
      $widgetService->updateWidgetPosition();
      return true;
    } catch (\Exception $e) {
      abort(500, $e->getMessage());
    }
  }

}
