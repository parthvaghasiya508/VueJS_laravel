<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OneTimeLoginLink;
use App\Models\User;
use App\Support\Domain;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller {

  /*
  |--------------------------------------------------------------------------
  | Login Controller
  |--------------------------------------------------------------------------
  |
  | This controller handles authenticating users for the application and
  | redirecting them to your home screen. The controller uses a trait
  | to conveniently provide its functionality to your applications.
  |
  */

  use AuthenticatesUsers;

  /**
   * Where to redirect users after login.
   *
   * @return string
   */
  protected function redirectTo()
  {
    return Domain::getEffectiveExtranetDomain();
  }

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('guest')->except('logout');
  }

  protected function validateLogin(Request $request)
  {
    $request->validate([
      'uuid'            => 'sometimes|required|uuid',
      $this->username() => 'required_without:uuid|string',
      'password'        => 'required_without:uuid|string',
    ]);
  }

  protected function attemptLogin(Request $request)
  {
    if (!$uuid = $request->input('uuid')) {
      /** @var User $user */
      if ($user = User::query()->firstWhere($this->username(), $request->input($this->username()))) {
        if ($user->agent_id) {
          $request->session()->flash('error', 'auth.agents.forbidden.login');
          return false;
        }
      }
      return $this->guard()->attempt(
        $this->credentials($request), $request->filled('remember')
      );
    }
    // login by OTL
    $link = OneTimeLoginLink::findUnused($uuid);
    if (!$link) {
      $request->session()->flash('uuid', 'none');
      return false;
    }
    if ($link->isUsed) {
      $request->session()->flash('uuid', 'used');
      return false;
    }
    if ($link->isExpired) {
      $request->session()->flash('uuid', 'expired');
      return false;
    }
    $user = $link->user;
    $this->guard()->login($user);
    $request->session()->put('otl', $link);
    $link->markUsed();
    return true;
  }

  protected function sendFailedLoginResponse(Request $request)
  {
    if (!$request->has('uuid')) {
      throw ValidationException::withMessages([
        $this->username() => [trans($request->session()->get('error', 'auth.failed'))],
      ]);
    }
    throw ValidationException::withMessages([
      'uuid' => [trans('auth.agents.'.$request->session()->get('uuid', 'failed'))],
    ]);
  }

  /**
   * Redirect the user after determining they are locked out.
   *
   * @param \Illuminate\Http\Request $request
   *
   * @return void
   *
   * @throws \Illuminate\Validation\ValidationException
   */
  protected function sendLockoutResponse(Request $request)
  {
    $seconds = $this->limiter()->availableIn(
      $this->throttleKey($request)
    );

    $try_again_in = trans_choice('auth.throttle_try_again_in', $seconds, compact('seconds'));

    throw ValidationException::withMessages([
      $this->username() => [
        __('auth.throttle', compact('try_again_in')),
      ],
    ])->status(Response::HTTP_TOO_MANY_REQUESTS);
  }

  protected function authenticated(Request $request, User $user)
  {
    // $request->session()->put('group', $user->group);
  }
}
