<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Rules\Password;
use App\Support\Domain;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller {

  /*
  |--------------------------------------------------------------------------
  | Password Reset Controller
  |--------------------------------------------------------------------------
  |
  | This controller is responsible for handling password reset requests
  | and uses a simple trait to include this behavior. You're free to
  | explore this trait and override any methods you wish to tweak.
  |
  */

  use ResetsPasswords;

  public function __construct()
  {
    $this->middleware('throttle:6,1')->only('reset');
  }

  /**
   * Where to redirect users after resetting their password.
   *
   * @return string
   */
  protected function redirectTo()
  {
    return Domain::getEffectiveExtranetDomain();
  }

  protected function rules()
  {
    return [
      'token'    => 'required',
      'email'    => 'required|email',
      'password' => ['required', new Password],
    ];
  }
}
