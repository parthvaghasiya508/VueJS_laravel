<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {

  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  /**
   * @param Request $request
   *
   * @return User
   */
  protected function user(Request $request): User
  {
    return $request->user();
  }

  /**
   * @return Group|null
   */
  protected function group(): ?Group
  {
    return session('group');
  }

  /**
   * @return Hotel|null
   */
  protected function hotel(): ?Hotel
  {
    return session('hotel') ?: null;
  }

  /**
   * @return Agent|null
   */
  protected function agent(): ?Agent
  {
    return session('agent') ?: null;
  }
}
