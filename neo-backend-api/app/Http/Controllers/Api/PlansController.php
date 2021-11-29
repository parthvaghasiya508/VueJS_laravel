<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Cultuzz;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PlansController extends Controller {

  private function validationRules($edit = false)
  {
    $rules = [];
    if ($edit) {
      $rules += ['id' => 'required|numeric'];
    }
    $rules += [
      'langs' => 'required|array',
    ];
    foreach (PMSManager::$langs as $lang) {
      $rules += [
        "langs.$lang"      => ($lang === 'en' ? 'required' : 'sometimes').'|array',
        "langs.$lang.name" => ($lang === 'en' ? 'required' : 'nullable').'|string|min:1|max:200',
        "langs.$lang.desc" => 'nullable|string|min:1|max:5000',
      ];
    }
    $wds = PMSManager::allWeekdaysList();
    $rules += [
      'room'    => 'required|numeric',
      'meals'   => 'required|numeric',
      'bgarant' => 'required|numeric|in:'.implode(',', PMSManager::$defaultBookingGueranteeIDs),
      'policy'  => 'required|numeric',

      'cancels'   => 'sometimes|array',
      'cancels.*' => 'sometimes|numeric',

      'bdays'   => 'required|array',
      'bdays.*' => 'sometimes|in:'.$wds,
      'adays'   => 'required|array',
      'adays.*' => 'sometimes|in:'.$wds,
      'ddays'   => 'required|array',
      'ddays.*' => 'sometimes|in:'.$wds,

      'minlos' => 'required|numeric|min:1|max:999',
      'maxlos' => 'required|numeric|min:1|max:999|gte:minlos',

      'occupancy'     => 'required|array',
      'occupancy.min' => 'required|numeric|min:1|lte:occupancy.std',
      'occupancy.std' => 'required|numeric|min:1',
      'occupancy.max' => 'required|numeric|gte:occupancy.std',

      'validity'          => 'required|array',
      'validity.*.from'   => 'required|date_format:Y-m-d|lte:validity.*.until',
      'validity.*.until'  => 'required|date_format:Y-m-d',
      'validity.*.unlim'  => 'boolean',

      'blockouts'         => 'sometimes|array',
      'blockouts.*.from'  => 'required|date_format:Y-m-d|lte:blockouts.*.until',
      'blockouts.*.until' => 'required|date_format:Y-m-d',

      'bookable'      => 'required|array',
      'bookable.mode' => 'required|numeric|in:'.implode(',', Cultuzz::BOOKABLE),

      'bookable.periods'         => 'exclude_unless:bookable.mode,'.Cultuzz::BOOKABLE_PERIODS.'|required|array|min:1',
      'bookable.periods.*.from'  => 'required|date_format:Y-m-d|lte:bookable.periods.*.until',
      'bookable.periods.*.until' => 'required|date_format:Y-m-d',

      'bookable.from'   => 'exclude_unless:bookable.mode,'.Cultuzz::BOOKABLE_FROMTO.'|required|numeric:between:0,999',
      'bookable.to'     => 'exclude_unless:bookable.mode,'.Cultuzz::BOOKABLE_FROMTO.'|required|numeric:between:0,999',
      'bookable.until'  => 'exclude_unless:bookable.mode,'.Cultuzz::BOOKABLE_UNTIL.'|required|numeric:between:0,999',
      'bookable.within' => 'exclude_unless:bookable.mode,'.Cultuzz::BOOKABLE_WITHIN.'|required|numeric:between:0,999',

      'price'       => 'required|array',
      'price.mode'  => 'required|in:standard,fixed',
      'price.fixed' => 'required_if:price.mode,fixed|numeric',

      'price.stdcalc'                 => 'sometimes|array',
      'price.stdcalc.mode'            => 'required|in:surcharge,reduction',
      'price.stdcalc.surcharge'       => 'sometimes|array',
      'price.stdcalc.surcharge.mode'  => 'in:amount,percent',
      'price.stdcalc.surcharge.value' => 'nullable|numeric',
      'price.stdcalc.reduction'       => 'sometimes|array',
      'price.stdcalc.reduction.mode'  => 'in:amount,percent',
      'price.stdcalc.reduction.value' => 'nullable|numeric',

      'price.calc'                 => 'required|array',
      'price.calc.surcharge'       => 'sometimes|array',
      'price.calc.surcharge.mode'  => 'in:amount,percent',
      'price.calc.surcharge.value' => 'nullable|numeric',
      'price.calc.reduction'       => 'sometimes|array',
      'price.calc.reduction.mode'  => 'in:amount,percent',
      'price.calc.reduction.value' => 'nullable|numeric',

      'price.calc.children'                   => 'required|array|size:3',
      'price.calc.children.*.age'             => 'nullable|numeric|min:1|max:18',
      'price.calc.children.*.surcharge'       => 'required|array',
      'price.calc.children.*.surcharge.mode'  => 'required|in:amount,percent',
      'price.calc.children.*.surcharge.value' => 'nullable|numeric',
    ];
    return $rules;
  }

  /**
   * Get rate plans with additional info
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|Collection|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    $data = $manager->getRatePlansWithExtraData();
    $bgarants = PMSManager::defaultBookingGuarantees();
    $data += compact('bgarants');
    return $data;
  }

  public function roomTypesWithRatePlan(Request $request, PMSManager $manager)
  {
    $data = $manager->getRoomTypesAndRatePlans();
    return $data;
  }

  /**
   * Create rate plan
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @throws ValidationException
   */
  public function create(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->validationRules());
    $payload = $manager->purifyLangs($payload);
    $plan = $manager->modifyRatePlan($payload);
    return $plan;
  }

  /**
   * Update rate plan
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @throws ValidationException
   */
  public function update(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->validationRules(true));
    $payload = $manager->purifyLangs($payload);
    $plan = $manager->modifyRatePlan($payload);
    return $payload['langs'];
  }

  /**
   * Duplicate rate plan
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param int $id
   *
   * @return Response|array
   */
  public function duplicate(Request $request, PMSManager $manager, $id)
  {
    $payload = [
      '_copy' => true,
      'id'    => $id,
    ];
    $plan = $manager->modifyRatePlan($payload);
    return $plan;
  }

  /**
   * Delete rate plan
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param int $id
   *
   * @return Response|array
   */
  public function destroy(Request $request, PMSManager $manager, $id)
  {
    $payload = [
      '_delete' => true,
      'id'      => $id,
    ];
    $manager->modifyRatePlan($payload);
    return ['ok' => true];
  }
}
