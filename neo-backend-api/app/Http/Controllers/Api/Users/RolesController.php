<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Http\Resources\RolesCollection;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;

class RolesController extends Controller {

  /**
   * Returns all existing roles created by current user for current hotel
   *
   * @param Request $request
   *
   * @return RolesCollection
   */
  public function index(Request $request): RolesCollection
  {
    $user = $this->user($request);
    $hotel = $this->hotel();
    $roles = Role::listForHotel($hotel, $user);
//    $roles = $user->createdRoles()->withCount('users')->with('hotel:id,name')->where('hotel_id', $hotel->id)->get();
    return RoleResource::collection($roles);
  }

  /**
   * @param Request $request
   * @param Role|null $role
   * @param bool $delete
   *
   * @return array|true
   */
  private function validatePayloadAndRole(Request $request, Role $role = null, bool $delete = false)
  {
    if (!$delete) {
      $payload = $request->validate([
        'name'              => 'required|string|max:80',
        'inherit_from_user' => 'boolean',
        'pages'             => 'exclude_if:inherit_from_user,1|array',
        'pages.*'           => 'sometimes|exists:pages,name',
      ]);
    }
    $user = $this->user($request);
    $hotel = $this->hotel();
    $group = $this->group();
    if ($role) {
      if ($role->group_id != $group->id || $role->hotel_id != $hotel->id || $hotel->group_id != $group->id) {
        abort(403, 'Access Denied');
      }
      if ($role->user_id !== $user->id && !$user->isRootOf($role->user_id, $group)) {
        abort(403, 'Access Denied');
      }
    }
    if (!$delete) {
      if (!$payload['inherit_from_user']) {
        $allowedPages = $user->pagesForHotel($hotel);
        if (collect($payload['pages'])->diff($allowedPages)->count()) {
          abort(403, 'Access Denied');
        }
      }
      if (!$role) {
        $payload += [
          'user_id'  => $user->id,
          'hotel_id' => $hotel->id,
          'group_id' => optional($group)->id,
        ];
      }
    }
    return $delete ?: $payload;
  }

  /**
   * Creates new role
   *
   * @param Request $request
   *
   * @return RoleResource
   */
  public function store(Request $request): RoleResource
  {
    $payload = $this->validatePayloadAndRole($request);
    $role = Role::createNew($payload);
    $role->load('hotel:id,name')->loadCount('users');
    return RoleResource::make($role);
  }

  /**
   * @param Request $request
   * @param Role $role
   *
   * @return array
   */
  public function update(Request $request, Role $role): array
  {
    $payload = $this->validatePayloadAndRole($request, $role);
    $role->modify($payload);
    return ['ok' => true];
  }

  /**
   * @param Request $request
   * @param Role $role
   *
   * @return array
   * @throws Exception
   */
  public function destroy(Request $request, Role $role): array
  {
    $this->validatePayloadAndRole($request, $role, true);
    $role->delete();
    return ['ok' => true];
  }
}
