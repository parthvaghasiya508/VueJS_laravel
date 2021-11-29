<?php

namespace App\Http\Controllers\Api;

use App\Events\HotelRegistered;
use App\Events\SetupComplete;
use App\Http\Controllers\Controller;
use App\Lib\Cultuzz;
use App\Managers\PMSManager;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\HotelImage;
use App\Models\LogHotelStatus;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;
use RoomDB;

class HotelsController extends Controller
{

  /**
   * Returns hotels list
   *
   * @return array
   */
  public function list(
    Request $request,
    PMSManager $manager,
    Group $group = null
  ) {
    // TODO: check permissions?
    $query_params = explode('?', $request->getRequestUri());
    $cparams = $query_params[1] ? explode('&', $query_params[1]) : [];
    $param = [];
    foreach ($cparams as $param) {
      $splitted = explode('=', $param);
      if ($splitted && (count($splitted))) $params[$splitted[0]] = intval($splitted[1]);
    }
    $page = Arr::get($params, 'page', 1);
    $perPage = Arr::get($params, 'per_page', 10);

    $user = $this->user($request);
    $hotels = $user->hotels;
    $paginate = new LengthAwarePaginator($hotels->forPage($page, $perPage), $hotels->count(),
      $perPage, $page, ['path' => url('hotels')]
    );
    $collection = $paginate
      ->getCollection()
      ->transform(fn ($hotel) => Arr::get($h = $manager->getHotel($hotel), 'id', null)
        ? $h
        : $hotel);
    $paginate->setCollection($collection);

    return $paginate;
  }

  /**
   * Returns user's allowed pages for specified hotel
   *
   * @param Request $request
   * @param Hotel $hotel
   */
  public function getPages(Request $request, Hotel $hotel)
  {
    $user = $this->user($request);
    // `hotels` attribute contains available hotels with pages already set
    $found = $user->hotels->firstWhere('id', $hotel->id);
    if (!$found) {
      return false;
    }
    return $found->pages;
  }

  /**
   * Returns hotel status change logs
   *
   * @param Request $request
   * @param Hotel $hotel
   *
   * @return array
   */
  public function listStatusLogs(Request $request, Hotel $hotel)
  {
    // TODO: check permissions
    $user = $this->user($request);
    if (!$user->hotels->pluck('id')->contains($hotel->id)) {
      abort(404, 'Not found');
    }
    return LogHotelStatus::findByHotel($hotel->id);
  }

  /**
   * Get hotel data from cultuzz
   *
   * @param Request $request
   * @param Group|null $group
   * @param Hotel $hotel
   * @param PMSManager $manager
   *
   * @return Response|array
   */
  public function get(Request $request, ?Group $group, Hotel $hotel, PMSManager $manager): array
  {
    $user = $this->user($request);
    $asAdmin = $group && $group->id;
    if (!$asAdmin) {
      if (!$user->hotels->pluck('id')->contains($hotel->id)) {
        abort(404, 'Not found');
      }
    } else {
      if ($hotel->group_id !== $group->id) {
        abort(404, 'Not found');
      }
    }
    $data = $manager->getHotel($hotel);
    $hotel->update($data);
    $hotel = $hotel->fresh();
    if (!$asAdmin) {
      $hotel = $hotel->withPagesForUser($user);
    }
    $hotel['currency_code'] = $data['currency_code'] ?? 'EUR';
    return compact('hotel', 'data');
  }

  /**
   * @param bool $partial
   *
   * @return array
   */
  private function validationRules($partial = false): array
  {
    $rule = $partial ? 'sometimes' : 'required';

    $descriptions = collect(Cultuzz::DESCRIPTION_CODE_NAMES)->mapWithKeys(function ($key) use ($rule) {
      $key = strtolower($key);
      return ["descriptions.{$key}.langs.*" => "$rule|nullable|max:1000"];
    })->toArray();
    if ($partial) {
      $tel = [
        'tel' => "$rule",
      ];
    } else {
      $tel = [
        'tel' => "$rule|regex:/^\+\d{8,15}$/",
      ];
    }

    return [
      [
        'city'            => "$rule|string|max:255",
        'street'          => "$rule|string|max:255",
        'zip'             => "$rule|string|max:10",
        'type'            => "nullable|numeric",
        'state'           => "nullable|string",
        'country'         => "$rule|string|size:2",
        'name'            => "$rule|string|max:255",
        'email'           => "$rule|email",
        'lang'            => "sometimes|in:" . implode(',', Cultuzz::LANGS),
        'currency_code'   => "$rule|string",
        'capacity'        => 'sometimes|integer|between:1,9999',
        'capacity_mode'   => 'sometimes|integer|in:0,1',
        'website'         => 'sometimes', // TODO:: add |string|regex:/(https?:\/\/)?.*\..*[a-z]/
        'street_optional' => 'sometimes|nullable|max:255',
        'longitude'       => 'sometimes|nullable|numeric|between:-180,180',
        'latitude'        => 'sometimes|nullable|numeric|between:-90,90',
        'logo'            => 'sometimes|array',
        'logo.remove'     => 'boolean',
        'logo.upload'     => 'nullable|file|mimetypes:image/png,image/jpeg',
        'roomdb_is_master'=> 'sometimes|boolean',
      ] + $descriptions + $tel,
      [
        'tel.required'    => 'This field is required',
        'tel.regex'       => 'Invalid phone',
        'tel_intl.regex'  => 'Invalid phone',
        'country.size'    => 'Invalid country',
        'name.required'   => 'This field is required',
        'website.regex'   => 'Invalid URL',
        'lang.in'         => 'The language is not supported',
      ],
    ];
  }

  /**
   * @param Request $request
   * @param Group|null $group
   * @param PMSManager $manager
   *
   * @return Hotel|null
   */
  public function create(Request $request, ?Group $group, PMSManager $manager)
  {
    // TODO: check permissions
    $user = $this->user($request);
    $parentGroup = $this->group();
    $asAdmin = $group && $group->id;
    if ($asAdmin) {
      if (!$group->owner) {
        abort(400, 'Group has no owner yet');
      }
    }
    $partial = $request->input('partial');
    $payload = $request->validate(...$this->validationRules($partial));
    $payload['user_id'] = $asAdmin ? ($group->owner->id ?? null) : $user->id;
    $payload['group_id'] = $asAdmin ? $group->id : optional($parentGroup)->id;
    $hotel = $manager->registerHotel($payload);
    event(new HotelRegistered($user, ['hotel' => $hotel->id] + $payload));
    $return = $hotel->fresh()->withPagesForUser($user);
    $return['currency_code'] = $payload['currency_code'];
    return $return;
  }

  /**
   * @param Group $group
   * @param $id
   * @param PMSManager $manager
   *
   * @return Hotel|Model|null
   * @throws Exception
   */
  public function import(Request $request, Group $group, $id, PMSManager $manager)
  {
    if (!$group->owner) {
      abort(400, 'Group has no owner yet');
    }
    if (Hotel::query()->where('id', $id)->exists()) {
      abort(400, "Property $id already exists");
    }
    $group_id = $group->id;
    $user_id = $group->group_owner;
    $user = User::find($user_id);

    $hotel = Hotel::createEmpty(compact('id', 'group_id', 'user_id'));
    try {
      $data = $manager->getHotel($hotel);
      $hotel->update($data);
      $hotel = $hotel->fresh();
      event(new HotelRegistered($user, ['hotel' => $hotel->id]));
    } catch (Throwable $e) {
      $hotel->delete();
      abort(400, $e->getMessage());
    }
    return $hotel;
  }

  /**
   * @param Request $request
   * @param Group|null $group
   * @param Hotel $hotel
   * @param PMSManager $manager
   *
   * @return Response|Hotel|null
   */
  public function update(Request $request, ?Group $group, Hotel $hotel, PMSManager $manager)
  {
    $user = $this->user($request);
    $asAdmin = $group && $group->id;
    if (!$asAdmin) {
      if (!$user->hotels->pluck('id')->contains($hotel->id)) {
        abort(404, 'Not found');
      }
    } else {
      if ($hotel->group_id !== $group->id) {
        abort(404, 'Not found');
      }
    }
    $partial = $request->input('partial');
    $payload = $request->validate(...$this->validationRules($partial));
//    $payload['user_id'] = $user->id;
    if ($partial) {
      $payload = array_merge($manager->getHotel($hotel), $payload);
    }
    if (Arr::get($payload, 'logo.remove', false)) {
      // remove existing image
      optional($hotel->image)->delete();
    }
    if ($logo = Arr::get($payload, 'logo.upload')) {
      // create new image
      optional($hotel->image)->delete();
      HotelImage::create($logo, $user, $hotel);
    }
    if (Arr::has($payload, 'logo')) {
      $hotel = $hotel->fresh();
      Arr::forget($payload, 'logo');
    }
    $manager->modifyHotel($payload, $hotel);
    $hotel = $hotel->fresh();
    if (!$asAdmin) {
      $hotel = $hotel->withPagesForUser($user);
    }
    $hotelData = $manager->getHotel($hotel);
    $hotel['currency_code'] = ($hotelData && $hotelData['currency_code']) ? $hotelData['currency_code'] : 'EUR';
    return $hotel;
  }

  /**
   * Toggle Booking Service state
   *
   * @param Request $request
   * @param Group|null $group
   * @param Hotel $hotel
   * @param PMSManager $manager
   *
   * @return LogHotelStatus|null
   * @throws ValidationException
   */
  public function toggleStatus(Request $request, ?Group $group, Hotel $hotel, PMSManager $manager): ?LogHotelStatus
  {
    $asAdmin = $group && $group->id;
    $user = $this->user($request);
    if (!$asAdmin) {
      if (!$user->hotels->pluck('id')->contains($hotel->id)) {
        abort(404, 'Not found');
      }
    } else {
      if ($hotel->group_id !== $group->id) {
        abort(404, 'Not found');
      }
    }
    $data = $this->validate($request, [
      'active' => 'required|boolean',
    ]);
    $status = $data['active'];
    $manager->setCredentials($hotel, $user)->activateHotelBooking($status);
    return LogHotelStatus::make($hotel, $user, $status);
  }

  /**
   * Updates setup step
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return array
   * @throws ValidationException
   */
  public function updateSetupStep(Request $request, PMSManager $manager)
  {
    $user = $this->user($request);
    /** @var Hotel $hotel */
    $hotel = session()->get('hotel');
    $agent = $user->ofAgent;
    $oldStep = !$agent ? $user->setup_step : $hotel->agent_setup_step;
    $rules = [
      'step' => 'required|numeric|between:-1,6',
    ];
    if ($oldStep === 1) {
      $rules += [
        'name' => 'required_if:step,2|string|max:255',
        'email' => 'required_if:step,2|string|email',
        'tel' => 'required_if:step,2|regex:/^\+\d{8,15}$/',
      ];
    }
    $data = $this->validate($request, $rules, [
      'tel.required' => 'This field is required',
      'tel.regex' => 'Invalid phone',
    ]);
    $step = intval(Arr::pull($data, 'step'));
    if (!$agent && $oldStep === 1 && $step === 2) {
      // update masterdata
      $data = array_merge($manager->getHotel($hotel), $data);
      $manager->modifyHotel($data);
    }
    if ($agent && $step < 2) {
      $step = 2;
    }
    !$agent ? $user->updateSetupStep($step) : $hotel->updateAgentSetupStep($step);
    if (!$agent && $step < 0) {
      // cancel setup
      event(new SetupComplete($user));
    } elseif ($step == 5) {
      // collect data
      try {
        // activate booking service
        $manager->activateHotelBooking();
        $data = $manager->getProductsCount(true);
        $ids = Arr::pull($data, 'ids');
        // activate pull channel (1st time)
        $manager->modifyChannel(['mode' => 'start']);
        // map all rate plans
        $payload = [
          'rooms' => array_map(function ($id) {
            return ['id' => $id, 'inv' => true];
          }, $ids),
        ];
        $manager->modifyPullMappings($payload);
        // activate pull channel
        $manager->modifyChannel(['mode' => 'activate']);
      } catch (Throwable $e) {
        $data = [];
      }
      $data += [
        'photos' => $hotel->images()->count(),
      ];
      if (!$agent) {
        event(new SetupComplete($user, $data));
      }
    }
    // Just for try sake
    $user->hotels = Hotel::normalize($user->hotels);
    return !$agent
      ? $user->fresh()->only('setup_complete', 'setup_step', 'profile', 'hotels')
      : $hotel->fresh()->only('id', 'agent_setup_complete', 'agent_setup_step');
  }

  public function verifyPhone($phone)
  {
    return Http::get(config('cultuzz.numverify_url').'?access_key='.config('cultuzz.numverify_access_key').'&number='.$phone);
  }

  public function getPropertyData(int $hotelId): JsonResponse
  {
      $data = RoomDB::getPropertyDataBySupplierId($hotelId);
      return response()->json($data);
  }
}
