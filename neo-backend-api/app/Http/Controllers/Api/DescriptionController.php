<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Cultuzz;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DescriptionController extends Controller {

  /**
   * Get descriptions
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return array|bool|string|null
   */
  public function get(Request $request, PMSManager $manager)
  {
    return $manager->getDescriptions();
  }

  /**
   * Update descriptions
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return bool[]
   * @throws ValidationException
   */
  public function update(Request $request, PMSManager $manager): array
  {
    $rules = collect(Cultuzz::DESCRIPTION_CODE_NAMES)->mapWithKeys(function ($key) {
      $key = strtolower($key);
      return ["descriptions.{$key}.langs.*" => 'nullable|max:2000'];
    })->toArray();

    $payload = $this->validate($request, $rules);
    $hotel = $this->hotel();
    $payload = array_merge($manager->getHotel($hotel), $payload);
    $manager->updateDescription($payload, $hotel);
    return ['ok' => true];
  }
}
