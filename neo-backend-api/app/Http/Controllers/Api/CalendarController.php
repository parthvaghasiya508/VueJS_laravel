<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class CalendarController extends Controller {

  /**
   * Get countries list
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   */
  public function getRooms(Request $request, PMSManager $manager)
  {
    return $manager->getRooms(false, true);
  }

  /**
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array|Collection
   * @throws ValidationException
   */
  public function getRoomsData(Request $request, PMSManager $manager)
  {
    $data = $this->validate($request, [
//      'ids'   => 'required|array',
      'from'  => 'required|date_format:Y-m-d',
      'until' => 'required|date_format:Y-m-d',
    ]);
    return $manager->getRoomsData($data['from'], $data['until']);
  }

  private function roomUpdateRules()
  {
    return [
      'id'   => 'required|numeric',
      'day'  => 'required|date_format:Y-m-d',
      'data' => 'required|array',
    ];
  }

  public function updateRoomAvail(Request $request, PMSManager $manager)
  {
    $data = $this->validate($request, $this->roomUpdateRules() +
      [
        'data.avail' => 'required|numeric|min:0',
      ]);
    $manager->updateRoomAvailability($data);
    return ['ok' => true];
  }

  public function updateRoomData(Request $request, PMSManager $manager)
  {
    $data = $this->validate($request, $this->roomUpdateRules() +
      [
        'data.price'  => 'required|numeric|min:0',
        'data.minlos' => 'required|numeric|min:0',
        'data.maxlos' => 'required|numeric|min:0',
        'data.csale'  => 'required|boolean',
        'data.carr'   => 'required|boolean',
        'data.cdep'   => 'required|boolean',
      ]);
    $manager->updateRoomData($data);
    return ['ok' => true];
  }

  public function batchUpdateRooms(Request $request, PMSManager $manager)
  {
    $data = $this->validate($request, [
      'from'          => 'required|date_format:Y-m-d',
      'until'         => 'required|date_format:Y-m-d',
      'days'          => 'required|array',
      'days.*'        => 'string|in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
      'rooms'         => 'required|array',
      'rooms.*'       => 'numeric',
      'fields'        => 'required|array',
      'fields.avail'  => 'numeric|min:0',
      'fields.price'  => 'numeric|min:0.01|max:999999999.99',
      'fields.minlos' => 'numeric|min:1|max:999',
      'fields.maxlos' => 'numeric|min:1|max:999',
      'fields.osale'  => 'boolean',
      'fields.carr'   => 'boolean',
      'fields.cdep'   => 'boolean',
      'fields.grnt'   => 'numeric|in:1,3',
    ]);
    if (!$data['days'] || !$data['rooms'] || !$data['fields']) {
      throw new NotAcceptableHttpException('Invalid data');
    }
    $from = Carbon::createFromFormat('Y-m-d', $data['from'], 'UTC');
    $until = Carbon::createFromFormat('Y-m-d', $data['until'], 'UTC');
    if ($from->isBefore(now('UTC')->startOfDay())) {
      throw new NotAcceptableHttpException('From should not be in the past');
    }
    if ($until->isBefore($from)) {
      throw new NotAcceptableHttpException('Until should not be before From');
    }
    # prepare and save Until in payload, to avoid double parsing later
    $until->addDay();
//    $data['from'] = $from;
    $data['until'] = $until->format('Y-m-d');
    if (Arr::exists($data, 'fields.price')) {
      Arr::set($data, 'fields.price', number_format(floatval($data['price']), 2, '.', ''));
    }
    $manager->batchUpdateRooms($data);
    return ['ok' => true];
  }
}
