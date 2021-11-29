<?php

namespace App\Http\Middleware;

use App\Models\Group;
use App\Models\Hotel;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class Permission {

  /**
   * Handle an incoming request.
   *
   * @param Request $request
   * @param Closure $next
   * @param string ...$pages
   *
   * @return mixed
   */
  public function handle(Request $request, Closure $next, ...$pages)
  {
    /** @var User $user */
    if (!$user = $request->user()) {
      abort(401, 'Unauthenticated');
    }
    if ($user->admin || !$user->belongsToGroup()) {
      return $next($request);
    }

    // *** next code applies to group users only ***

    $checkPages = collect($pages);
    // check group admin pages first
    $groupAdminPages = $user->adminPagesForGroup();
    $allow = $checkPages->some(fn ($page) => $groupAdminPages->contains($page));
    if ($allow) {
      return $next($request);
    }

    /** @var Group $group */
    $group = session('group');
    /** @var Hotel $hotel */
    $hotel = session('hotel');
    if (!$hotel || !$group || $hotel->group_id !== $group->id) {
      abort(403, 'Access Denied');
    }
    $hotelPages = $user->pagesForHotel($hotel);
    if (!$hotelPages) {
      // no pages permissions
      abort(403, 'Access Denied');
    }
    $allow = $checkPages->some(fn ($page) => $hotelPages->contains($page));
    if (!$allow) {
      abort(403, 'Access Denied');
    }
    return $next($request);
  }
}
