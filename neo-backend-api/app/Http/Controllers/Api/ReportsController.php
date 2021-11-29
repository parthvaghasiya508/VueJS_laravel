<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\CDManager;
use App\Models\Hotel;
use App\Services\Dashboard\WidgetService;
use Illuminate\Support\Arr;

class ReportsController extends Controller {

  /**
   * Get recent report
   *
   * @param CDManager $manager
   *
   * @return array|null
   */
  public function getRecent(CDManager $manager): ?array
  {
    /** @var Hotel $hotel */
    $hotel = session('hotel');
    $clientId = $hotel->id;
    $reports = $manager->getReports($clientId);
    if (Arr::get($reports, 'totalRecords', 0) < 1) {
      return null;
    }
    return collect(Arr::get($reports, 'data'))->sortByDesc('month')->first();
  }

  /**
   * @param CDManager $manager
   * @return array|null
   */
  public function getDashboard(CDManager $manager): ?array
  {
    return $manager->getDashboard($this->hotel()->id);
  }

  /**
   * @param WidgetService $widgetService
   * @return array|null
   */
  public function getSettings(
    WidgetService $widgetService
  ): ?array {
    return $widgetService->getWidgetSettingsByUser();
  }
}
