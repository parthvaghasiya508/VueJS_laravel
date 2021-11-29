<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Cultuzz;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class FacilitiesController extends Controller {

  /**
   * Get facilities
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    $available = Cultuzz::HAC_CODES;
    $facilities = $manager->getFacilities();
    return compact('facilities', 'available');
  }

  /**
   * Update facilities
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   * @throws ValidationException
   */
  public function update(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, [
      'facilities' => 'sometimes|array',
    ]);
    $manager->updateFacilities($payload);
    return ['ok' => true];
  }
}
