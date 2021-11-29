<?php

namespace App\Http\Middleware;

use App\Managers\PMSManager;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureHotelIsValid
{
  /**
   * Handle an incoming request.
   *
   * @param Request $request
   * @param Closure $next
   * @return mixed
   */
  public function handle(Request $request, Closure $next, $required = true)
  {
    /** @var User $user */
    if (!$user = $request->user()) {
      abort(401, 'Unauthenticated');
    }
    if (!$id = $request->header('x-hotel-id')) {
      if (!$required) {
        $group = Group::firstWhere('group_owner', $user->id);
        $request->session()->now('group', $group);
        return $next($request);
      }
      abort(403, 'Hotel is not set');
    }
    // TODO: check user permissions for this hotel
    /** @var Hotel $hotel */
    if (!$hotel = Hotel::query()->find($id)) {
      abort(403, 'Wrong hotel');
    }
    // check whether the user has access to this hotel
    if (!$user->hasAccessToHotel($hotel)) {
      abort(403, 'Access Denied');
    }
    app()->make(PMSManager::class)->setCredentials($hotel, $user);
    $request->session()->now('hotel', $hotel);
    $request->session()->now('group', $hotel->group);
    return $next($request);
  }
}
