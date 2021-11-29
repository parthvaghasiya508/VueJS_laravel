<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Cultuzz;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class NearByController extends Controller {

  /**
   * Get near by data
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    return $manager->getNearBy();
  }

  /**
   * Update near by data
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   * @throws ValidationException
   */
  public function update(Request $request, PMSManager $manager)
  {
    $rules = collect(Cultuzz::REF_POINT_CODES)->mapWithKeys(fn ($key) => [
      $key                => 'array',
      "{$key}.*.changed"  => 'required|boolean',
      "{$key}.*.code"     => 'required|string|between:1,15',
      "{$key}.*.distance" => 'required|numeric|between:1,99999',
      "{$key}.*.unit"     => 'required|in:' . implode(',', array_keys(Cultuzz::$distanceUnits)),
    ])->toArray();
    $payload = $this->validate($request, $rules);
    $manager->updateNearBy($payload);
    return ['ok' => true];
  }
}
