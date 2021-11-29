<?php

namespace App\Http\Controllers\Api\Users;

use Illuminate\Validation\ValidationException;
use App\Http\Resources\UsersCollection;
use App\Http\Controllers\Controller;
use App\Http\Requests\InviteUserToJoinPropertyFormRequest;
use App\Http\Resources\UserResource;
use App\Models\Group;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Rules\Password;
use App\Models\Hotel;
use App\Models\Role;
use App\Models\User;
use Exception;

class UsersController extends Controller {

  /**
   * @param Request $request
   * @param User|null $user
   * @param bool $delete
   *
   * @return array|true
   */
  private function validatePayloadAndRoles(Request $request, User $user = null, bool $delete = false)
  {
    $parent = $this->user($request);
    if (!$delete) {
      $requiredUnlessEdit = $user ? 'sometimes' : 'required';
      $rules = [
        'email'              => [$requiredUnlessEdit, 'string', 'email', 'max:255'], // should be in array form
        'password'           => ['sometimes', 'string', new Password],
        'profile.first_name' => 'string|max:255',
        'profile.last_name'  => 'string|max:255',
        'profile.tel'        => 'regex:/^\+\d{8,15}$/',
        'lang'               => 'required|string|size:2',
        'avatar.upload'      => 'nullable|file|mimetypes:image/png,image/jpeg',
        'roles'              => [$requiredUnlessEdit, 'array'],
        'roles.*'            => 'numeric|exists:roles,id',
        'all_group_hotels'   => 'boolean',
        'apages'             => 'array',
        'apages.*'           => 'sometimes|exists:pages,name',
        'pages'              => 'array',
        'pages.*'            => 'sometimes|exists:pages,name',
      ];
      if ($parent->admin && !$user) {
        $rules['roles'] = 'nullable|array';
        $rules += ['group_id' => 'required|numeric|exists:groups,id'];
      }
      $uniq = Rule::unique('users');
      if ($user) {
        $uniq = $uniq->ignore($user->id);
        $rules += [
          'avatar.remove' => 'boolean',
        ];
      }
      $rules['email'][] = $uniq;
      $payload = $request->validate($rules);
    }

    $hotel = $this->hotel();
    $group = $this->group();
    if ($user) {
      if (!$parent->admin && $user->belongsToGroup($group) && $user->ownsGroup($group)) {
        abort(403, 'Access Denied');
      }
      if ($parent->id === $user->id) {
        abort(403, 'Access Denied');
      }
      if ($group && !$user->belongsToGroup($group)) {
        abort(403, 'Access Denied');
      }
      if (!$user->isSubordinateOf($parent->id, $group)) {
        abort(403, 'Access Denied');
      }
    }
    if (!$delete) {
      if ($hotel && !$hotel->isHotelRoles(Arr::get($payload, 'roles', []))) {
        abort(403, 'Access Denied');
      }
      if (collect(Arr::get($payload, 'apages', []))->diff($parent->adminPagesForGroup())->isNotEmpty()) {
        abort(403, 'Access Denied');
      }
      if (collect(Arr::get($payload, 'pages', []))->diff($parent->pagesForHotel($hotel))->isNotEmpty()) {
        abort(403, 'Access Denied');
      }
      if (!$user) {
        $payload += [
          'parent' => $parent,
        ];
      } else {
        $payload += [
          'hotel' => $hotel,
        ];
      }
    }

    return $delete ?: $payload;
  }

  /**
   * @param Request $request
   *
   * @return UsersCollection
   */
  public function index(Request $request): UsersCollection
  {
    $user = $this->user($request);
    $hotel = $this->hotel();
    $group = $this->group();

    $users = User::listWithRolesForHotel($group, $hotel, $user);
    return UserResource::collection($users);
  }

  /**
   * @param Request $request
   *
   * @return UserResource
   * @throws ValidationException
   */
  public function inviteToJoinHotel(InviteUserToJoinPropertyFormRequest $request)
  {
    $parent = $this->user($request);
    $payload = $request->all();

    // check that roles belong to current hotel
    // user's permission has already been checked by middleware
    $hotel = $this->hotel();
    $group = $this->group();
    if ($hotel && !$hotel->isHotelRoles(Arr::get($payload, 'roles', []))) {
      throw ValidationException::withMessages(['roles' => 'Invalid roles']);
    }

    $user = User::firstWhere('email', $payload['email']);
    if (User::hasAlreadyRoles($user, Arr::get($payload, 'roles', []))) {
      abort(409, 'User has already got that role');
    }
    $user = User::addHotelAccessToUser($hotel, $parent, $group, $payload);
    $user = User::listWithRolesForHotel($group, $hotel, $parent, $user->id);

    return UserResource::make($user);
  }

  /**
   * Confirm User accesses
   * to Hotel
   *
   * @param Request $request
   * @param User $user
   * @param Hotel $hotel
   *
   * @return UserResource
   */
  public function confirm(Request $request, User $user, Hotel $hotel)
  {
    if (!$request->filled('signature') || !$request->filled('expires')) {
      abort(403, 'Invalid URL');
    }
    $user = User::confirmHotelRolesForUser($hotel, $user);
    return UserResource::make($user);
  }

  /**
   * @param Request $request
   *
   * @return UserResource
   * @throws ValidationException
   */
  public function store(Request $request): UserResource
  {
    $root = $this->user($request);
    $payload = $this->validatePayloadAndRoles($request);

    // check that roles belong to current hotel
    // user's permission has already been checked by middleware

    $hotel = $this->hotel();
    if ($hotel && !$hotel->isHotelRoles(Arr::get($payload, 'roles', []))) {
      throw ValidationException::withMessages(['roles' => 'Invalid roles']);
    }
    $group_id = Arr::get($payload, 'group_id');
    $group = $root->admin && $group_id ? Group::find($group_id) : $this->group();

    $invitee = User::createUserInvite($root, $payload, $group);
    $invitee = $root->admin
      ? User::listWithRolesForGroup($group_id, $invitee->id)
      : User::listWithRolesForHotel($group, $hotel, $root, $invitee->id);

    return UserResource::make($invitee);
  }

  /**
   * Update the specified resource in storage.
   *
   * @param Request $request
   * @param User $user
   *
   * @return UserResource
   */
  public function update(Request $request, User $user): UserResource
  {
    $payload = $this->validatePayloadAndRoles($request, $user);
    $hotel = $this->hotel();
    $group = $this->group();

    $root = $this->user($request);
    $user = $user->modify($payload);
    $user = User::listWithRolesForHotel($group, $hotel, $root, $user->id);
    return UserResource::make($user);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param Request $request
   * @param User $user
   *
   * @return array
   * @throws Exception
   */
  public function destroy(Request $request, User $user): array
  {
    $this->validatePayloadAndRoles($request, $user, true);
    $parent = $this->user($request);
    $hotel = $this->hotel();
    $group = $this->group();

    if (!$hotel || !$user->isSubordinateOf($parent->id, $group)) {
      abort(403, 'Access forbidden !');
    }

    if ($user->ownsGroup($group)) {
      abort(409, 'Can\'t delete group owner');
    }

    User::removeAccessToHotel($hotel, $user);

    return ['ok' => true];
  }
}
