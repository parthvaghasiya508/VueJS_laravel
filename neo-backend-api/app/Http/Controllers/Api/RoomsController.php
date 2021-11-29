<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Cultuzz;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RoomsController extends Controller {

  private function validationRules($edit = false)
  {
    $rules = [
      'setup' => 'sometimes|boolean',
    ];
    if ($edit) {
      $rules += [
        'id'  => 'required|numeric',
        'pid' => 'required|numeric',
      ];
    } else {
      $rules += [
        'initprice' => 'required|numeric|min:0|max:'.Cultuzz::MAX_PRICE,
      ];
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
    $rules += [
      'amount'   => 'required|numeric|min:1|max:999',
      'typecode' => 'required|numeric',

      'occupancy'     => 'required|array',
      'occupancy.min' => 'nullable|numeric|min:1|lte:occupancy.std',
      'occupancy.std' => 'required|numeric|min:1',
      'occupancy.max' => 'nullable|numeric|gte:occupancy.std',

      'validity'          => 'required|array',
      'validity.*.from'   => 'required|date_format:Y-m-d|lte:validity.*.until',
      'validity.*.until'  => 'required|date_format:Y-m-d',
      'validity.*.unlim'  => 'boolean',

      'blockouts'         => 'sometimes|array',
      'blockouts.*.from'  => 'required|date_format:Y-m-d|lte:blockouts.*.until',
      'blockouts.*.until' => 'required|date_format:Y-m-d',
    ];
    return $rules;
  }

  /**
   * Get room types
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|Collection|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    $rooms = $manager->getRooms();
    $typecodes = Cultuzz::ROOM_TYPE_CODES;
    return compact('rooms', 'typecodes');
  }

  /**
   * Create room type
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
    $ret = $manager->modifyRoomType($payload);
    if (Arr::get($payload, 'setup')) {
      $payload['pid'] = $ret['pid'];
      $manager->createRatePlanFromRoomType($payload);
      $ret['protected'] = true;
    }
    return $ret;
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
    $manager->modifyRoomType($payload);
    return $payload['langs'];
  }

  /**
   * Duplicate room type
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
      'pid'   => $id,
    ];
    return $manager->modifyRoomType($payload);
  }

  /**
   * Delete room type
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
      'pid'     => $id,
    ];
    $manager->modifyRoomType($payload);
    return ['ok' => true];
  }
}
