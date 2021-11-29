<?php

namespace App\Http\Controllers\RoomDB;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentHotelResource;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class HotelController extends Controller {

  /**
   * @param bool $partial
   *
   * @return array
   */
  private function validationRules(bool $partial = false): array
  {
    $rule = $partial ? 'sometimes' : 'required';
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
        'id'              => "$rule|string|min:6"
      ],
    ];
  }

  /**
   * @param Request $request
   * @return AgentHotelResource
   */
  public function create(Request $request): AgentHotelResource
  {
    $payload = $request->validate(...$this->validationRules());
    try {
      $user = User::where('email', config('cultuzz.roomdb_user_email'))->first();
      if (!$user) {
        $user = User::create([
          'email'      => config('cultuzz.roomdb_user_email'),
          'password'   => Hash::make(config('cultuzz.roomdb_user_pwd')),
          'tos_agreed' => true,
          'admin'      => true,
        ]);
        $user->email_verified_at = Carbon::now();
        $user->save();
      }
      $payload['ctx']     = 'CLTZ';
      $payload['user_id'] = $user->id;
      $hotel = Hotel::create($payload);
      return AgentHotelResource::make($hotel->fresh());
    } catch (\Exception $exception) {
      Log::channel('roomdb')->error($exception->getMessage());
      abort(500, 'Server Error');
    }
  }
}
