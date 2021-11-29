<?php

namespace App\Http\Controllers\Api;

use App\Events\HotelRegistered;
use App\Events\ProfileFilled;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserFormRequest;
use App\Http\Resources\UserResource;
use App\Managers\PMSManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller {

  /**
   * Returns an user
   *
   * @param Request $request
   *
   * @return UserResource|null
   */
  public function get(Request $request): ?UserResource
  {
    $user = $this->user($request);
    $group = $this->group();
    $user->setUserPages($group);
//    $user->setHotelsPages();
//    $hotel = $this->hotel();
//    $user->perms = $user->pagesForHotel($hotel);
    return UserResource::make($user);
  }

  /**
   * Set user profile and register hotel
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|User
   * @throws ValidationException
   */
  public function setProfile(Request $request, PMSManager $manager)
  {
    /** @var User $user */
    $user = $request->user();
    if (!$user->pdFilled) {
      $data = $this->validate($request, [
        'first_name' => 'required|string|max:255',
        'last_name'  => 'required|string|max:255',
        'tel'        => 'required|regex:/^\+\d{8,15}$/',
      ]);
      $user->updatePersonalDetails($data);
      event(new ProfileFilled($user));
    } elseif (!$user->cdFilled) {
      $data = $this->validate($request, [
        'skip'          => 'sometimes|boolean',
        'name'          => 'exclude_if:skip,1|required|string|max:255',
        'city'          => 'exclude_if:skip,1|required|string|max:255',
        'street'        => 'exclude_if:skip,1|required|string|max:255',
        'zip'           => 'exclude_if:skip,1|required|string|max:10',
        'htel'          => 'exclude_if:skip,1|required|regex:/^\+\d{8,15}$/',
        'country'       => 'exclude_if:skip,1|required|string|size:2',
        'email'         => 'exclude_if:skip,1|required|email',
        'currency_code' => "exclude_if:skip,1|required|size:3",
      ], [
        'hotel_name.required' => 'This field is required',
        'htel.required'       => 'This field is required',
        'htel.regex'          => 'Invalid phone',
        'country.size'         => 'Invalid country',
      ]);

      if (Arr::get($data, 'skip')) {
        $user->cd_filled_at = $user->freshTimestamp();
        $user->setup_at = $user->freshTimestamp();
        $user->setup_step = 0;
        $user->save();
        event(new HotelRegistered($user, ['skip' => true]));
      } else {
        $hotel = $this->hotel();
        $data['user_id'] = $user->id;
        $data['group_id'] = optional($hotel)->group_id;
        // register hotel object
        try {
          Arr::set($data, 'tel', Arr::get($data, 'htel'));
          $hotel = $manager->registerHotel($data);
          $user->cd_filled_at = $user->freshTimestamp();
          $user->save();
          event(new HotelRegistered($user, ['hotel' => $hotel->id] + $data));
        } catch (Throwable $e) {
          throw ValidationException::withMessages(['hotel' => $e->getMessage()]);
        }
      }
    }
    $user = $user->fresh();
    $group = $this->group();
    $user->setUserPages($group);

    return $user;
  }

  /**
   * Update the user.
   *
   * @param Request $request
   *
   * @return User|null
   * @throws ValidationException
   *
   */
  public function update(UpdateUserFormRequest $request)
  {
    $data = $request->all();
    $user = $request->user();
    $user->updatePersonalDetails($data);
    return $user->fresh();
  }
}
