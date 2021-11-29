<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Cultuzz;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MealPlansController extends Controller {

  /**
   * @param bool $edit
   *
   * @return array|string[]
   */
  private function validationRules($edit = false): array
  {
    $rules = [
      'setup' => 'sometimes|boolean',
    ];
    if ($edit) {
      $rules += [
        'id' => 'required|numeric',
      ];
    }
    $rules += [
      'langs' => 'required|array',
    ];
    foreach (PMSManager::$langs as $lang) {
      $rules += [
        "langs.$lang"      => ($lang === 'en' ? 'required' : 'sometimes').'|array',
        "langs.$lang.name" => ($lang === 'en' ? 'required' : 'nullable').'|string|min:1|max:200',
        "langs.$lang.desc" => 'nullable|string|min:1|max:500',
      ];
    }
    $rules += [
      'price'    => 'required|numeric|min:0|max:'.Cultuzz::MAX_PRICE,
      'typecode' => 'required|numeric',

      'validity'         => 'sometimes|array',
      'validity.*.from'  => 'required|date_format:Y-m-d|lte:validity.*.until',
      'validity.*.until' => 'required|date_format:Y-m-d',

      'blockouts'         => 'sometimes|array',
      'blockouts.*.from'  => 'required|date_format:Y-m-d|lte:blockouts.*.until',
      'blockouts.*.until' => 'required|date_format:Y-m-d',

      'prices'         => 'sometimes|array',
      'prices.*.from'  => 'required|date_format:Y-m-d|lte:prices.*.until',
      'prices.*.until' => 'required|date_format:Y-m-d',
      'prices.*.price' => 'nullable|numeric|min:0|max:'.Cultuzz::MAX_PRICE,
    ];
    return $rules;
  }

  /**
   * Get meal plans List
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|Collection|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    $mealplans = $manager->getMealPlans();
    $typecodes = Cultuzz::MEAL_TYPES;
    return compact('mealplans', 'typecodes');
  }

  /**
   * Create meal plan
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array|bool
   * @throws ValidationException
   */
  public function create(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->validationRules());
    $payload = $manager->purifyLangs($payload);
    return $manager->modifyMealPlan($payload);
  }

  /**
   * Update room type
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   * @throws ValidationException
   */
  public function update(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->validationRules(true));
    $payload = $manager->purifyLangs($payload);
    $manager->modifyMealPlan($payload);
    return $payload['langs'];
  }

  /**
   * Duplicate meal plan
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param int $id
   *
   * @return Response|array
   */
  public function duplicate(Request $request, PMSManager $manager, int $id)
  {
    $payload = [
      '_copy' => true,
      'id'    => $id,
    ];
    return $manager->modifyMealPlan($payload);
  }

  /**
   * Delete meal plan
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param int $id
   *
   * @return Response|array
   */
  public function destroy(Request $request, PMSManager $manager, int $id)
  {
    $payload = [
      '_delete' => true,
      'id'      => $id,
    ];
    $manager->modifyMealPlan($payload);
    return ['ok' => true];
  }
}
