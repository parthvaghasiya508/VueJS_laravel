<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentHotelResource;
use App\Http\Resources\AgentHotelsCollection;
use App\Http\Resources\AgentUserResource;
use App\Http\Resources\AgentUsersCollection;
use App\Lib\Cultuzz;
use App\Managers\PMSManager;
use App\Models\Hotel;
use App\Models\HotelImage;
use App\Models\LogHotelStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class HotelsController extends Controller {

  /**
   * Returns hotels list for an user
   *
   * @param Hotel $agentHotel
   *
   * @return AgentUsersCollection
   */
  public function listOfUsers(Hotel $agentHotel): AgentUsersCollection
  {
    return AgentUserResource::collection($agentHotel->agentUsers);
  }

  /**
   * Returns all hotels for agent
   *
   * @return AgentHotelsCollection
   */
  public function list(): AgentHotelsCollection
  {
    $agent = $this->agent();
    return AgentHotelResource::collection($agent->hotels);
  }

  /**
   * Get user's hotel data from cultuzz
   *
   * @param User $agentUser
   * @param Hotel $hotel
   * @param PMSManager $manager
   *
   * @return AgentHotelResource
   */
  public function getOfUser(User $agentUser, Hotel $hotel, PMSManager $manager): AgentHotelResource
  {
    if (!$agentUser->hotels->pluck('id')->contains($hotel->id)) {
      abort(404, 'Not found');
    }
    $data = $manager->getHotel($hotel);
    $hotel->update($data);
    return AgentHotelResource::make($hotel->fresh(), $data);
  }

  /**
   * Get agent's hotel data from cultuzz
   *
   * @param Hotel $agentHotel
   * @param PMSManager $manager
   *
   * @return AgentHotelResource
   */
  public function get(Hotel $agentHotel, PMSManager $manager): AgentHotelResource
  {
    $data = $manager->getHotel($agentHotel);
    $agentHotel->update($data);
    return AgentHotelResource::make($agentHotel->fresh(), $data);
  }

  /**
   * @param bool $partial
   *
   * @return array
   */
  private function validationRules(bool $partial = false): array
  {
    $rule = $partial ? 'sometimes' : 'required';

    $descriptions = collect(Cultuzz::DESCRIPTION_CODE_NAMES)->mapWithKeys(function ($key) use ($rule) {
      $key = strtolower($key);
      return ["descriptions.{$key}.lang.*" => "$rule|string|between:1,1000"];
    })->toArray();

    return [
      [
        'city'            => "$rule|string|max:255",
        'street'          => "$rule|string|max:255",
        'zip'             => "$rule|string|max:10",
        'tel'             => "$rule|regex:/^\+\d{8,15}$/",
        'country'         => "$rule|string|size:2",
        'name'            => "$rule|string|max:255",
        'email'           => "$rule|email",
        'currency'        => "$rule|string|exists:currencies,code",
        'capacity'        => 'sometimes|integer|between:1,999',
        'capacity_mode'   => 'sometimes|integer|in:0,1',
        'website'         => 'sometimes|url',
        'street_optional' => 'sometimes|nullable|max:255',
        'longitude'       => 'sometimes|nullable|numeric|between:-180,180',
        'latitude'        => 'sometimes|nullable|numeric|between:-90,90',
        'logo'            => 'sometimes|array',
        'logo.remove'     => 'boolean',
        'logo.upload'     => 'nullable|file|mimetypes:image/png,image/jpeg',
      ] + $descriptions, [
        'tel.required'  => 'This field is required',
        'tel.regex'     => 'Invalid phone',
        'country.size'  => 'Invalid country',
        'name.required' => 'This field is required',
        'website.regex' => 'Invalid URL',
      ],
    ];
  }

  /**
   * @param Request $request
   * @param User $agentUser
   * @param PMSManager $manager
   *
   * @return AgentHotelResource
   */
  public function createOfUser(Request $request, User $agentUser, PMSManager $manager): AgentHotelResource
  {
    $payload = $request->validate(...$this->validationRules());
    $payload['user_id'] = $agentUser->id;
    $payload['agent_setup_step'] = 2;
    $payload['group_id'] = $this->agent()->group_id;
    $hotel = $manager->registerHotel($payload);
    // event(new HotelRegistered($agentUser, ['hotel' => $hotel->id] + $payload));
    if ($logo = Arr::get($payload, 'logo.upload')) {
      // create new image
      HotelImage::create($logo, $agentUser, $hotel);
      $manager->modifyHotel($payload, $hotel);
    }
    $agentUser->agentHotels()->attach($hotel);
    return AgentHotelResource::make($hotel->fresh());
  }

  /**
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return AgentHotelResource
   */
  public function create(Request $request, PMSManager $manager): AgentHotelResource
  {
    $payload = $request->validate(...$this->validationRules());
    $payload['agent_id'] = $this->agent()->id;
    $payload['agent_setup_step'] = 2;
    $payload['group_id'] = $this->agent()->group_id;
    $hotel = $manager->registerHotel($payload);
    // event(new HotelRegistered($agentUser, ['hotel' => $hotel->id] + $payload));
    if ($logo = Arr::get($payload, 'logo.upload')) {
      // create new image
      HotelImage::create($logo, null, $hotel);
      $manager->modifyHotel($payload, $hotel);
    }
    return AgentHotelResource::make($hotel->fresh());
  }

  /**
   * @param Request $request
   * @param User $agentUser
   * @param Hotel $hotel
   * @param PMSManager $manager
   *
   * @return AgentHotelResource
   */
  public function updateOfUser(Request $request, User $agentUser, Hotel $hotel, PMSManager $manager): AgentHotelResource
  {
    if (!$agentUser->hotels->pluck('id')->contains($hotel->id)) {
      abort(404, 'Not found');
    }
    $partial = true;
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
      HotelImage::create($logo, $agentUser, $hotel);
    }
    if (Arr::has($payload, 'logo')) {
      $hotel = $hotel->fresh();
      Arr::forget($payload, 'logo');
    }
    $manager->modifyHotel($payload, $hotel);
    return AgentHotelResource::make($hotel->fresh());
  }

  /**
   * @param Request $request
   * @param Hotel $agentHotel
   * @param PMSManager $manager
   *
   * @return AgentHotelResource
   */
  public function update(Request $request, Hotel $agentHotel, PMSManager $manager): AgentHotelResource
  {
    $partial = true;
    $payload = $request->validate(...$this->validationRules($partial));
//    $payload['user_id'] = $user->id;
    if ($partial) {
      $payload = array_merge($manager->getHotel($agentHotel), $payload);
    }
    if (Arr::get($payload, 'logo.remove', false)) {
      // remove existing image
      optional($agentHotel->image)->delete();
    }
    if ($logo = Arr::get($payload, 'logo.upload')) {
      // create new image
      optional($agentHotel->image)->delete();
      HotelImage::create($logo, null, $agentHotel);
    }
    if (Arr::has($payload, 'logo')) {
      $agentHotel = $agentHotel->fresh();
      Arr::forget($payload, 'logo');
    }
    $manager->modifyHotel($payload, $agentHotel);
    return AgentHotelResource::make($agentHotel->fresh());
  }

  /**
   * Toggle Booking Service state
   *
   * @param Request $request
   * @param User $agentUser
   * @param Hotel $hotel
   * @param PMSManager $manager
   *
   * @return array
   * @throws ValidationException
   */
  public function toggleStatusOfUser(Request $request, User $agentUser, Hotel $hotel, PMSManager $manager): array
  {
    if (!$agentUser->hotels->pluck('id')->contains($hotel->id)) {
      abort(404, 'Not found');
    }
    $data = $this->validate($request, [
      'active' => 'required|boolean',
    ]);
    $status = $data['active'];
    $manager->setCredentials($hotel, $agentUser)->activateHotelBooking($status);
    LogHotelStatus::make($hotel, $agentUser, $status);
    return ['ok' => true];
  }

  /**
   * Toggle Booking Service state
   *
   * @param Request $request
   * @param Hotel $agentHotel
   * @param PMSManager $manager
   *
   * @return array
   * @throws ValidationException
   */
  public function toggleStatus(Request $request, Hotel $agentHotel, PMSManager $manager): array
  {
    $data = $this->validate($request, [
      'active' => 'required|boolean',
    ]);
    $status = $data['active'];
    $manager->setCredentials($agentHotel)->activateHotelBooking($status);
    LogHotelStatus::make($agentHotel, null, $status);
    return ['ok' => true];
  }

  public function addUser(Hotel $agentHotel, User $agentUser): array
  {
    $agentHotel->agentUsers()->sync([$agentUser->id], false);
    return ['ok' => true];
  }

  public function deleteUser(Hotel $agentHotel, User $agentUser): array
  {
    $agentHotel->agentUsers()->detach($agentUser);
    return ['ok' => true];
  }

  public function setUsers(Request $request, Hotel $agentHotel): array
  {
    $agent = $this->agent();
    $data = $request->validate([
      'ids'   => 'required|array',
      'ids.*' => 'numeric',
    ]);
    $ids = User::query()->where('agent_id', $agent->id)->whereIn('agent_user_id', Arr::get($data, 'ids'))->get(['id'])->pluck('id');
    $agentHotel->agentUsers()->sync($ids);
    return ['ok' => true];
  }

  public function clearUsers(Hotel $agentHotel): array
  {
    $agentHotel->agentUsers()->sync([]);
    return ['ok' => true];
  }
}
