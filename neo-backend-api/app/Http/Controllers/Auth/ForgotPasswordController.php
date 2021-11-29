<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller {

  /*
  |--------------------------------------------------------------------------
  | Password Reset Controller
  |--------------------------------------------------------------------------
  |
  | This controller is responsible for handling password reset emails and
  | includes a trait which assists in sending these notifications from
  | your application to your users. Feel free to explore this trait.
  |
  */

  use SendsPasswordResetEmails;

  public function __construct()
  {
    $this->middleware('throttle:6,1')->only('sendResetLinkEmail');
  }

  /**
   * @throws ValidationException
   */
  protected function validateEmail(Request $request)
  {
    $request->validate(['email' => 'required|email']);
    /** @var User $user */
    $user = User::query()->firstWhere('email', $request->input('email'));
    if ($user && $user->agent_id) {
      throw ValidationException::withMessages([
        'email' => [trans('auth.agents.forbidden.reset')],
      ]);
    }
  }

  /**
   * Get the response for a successful password reset link.
   *
   * @param Request $request
   * @param string $response
   *
   * @return RedirectResponse|JsonResponse
   */
  protected function sendResetLinkResponse(Request $request, $response)
  {
    return $request->wantsJson()
      ? new JsonResponse(['message' => trans($response)], 200)
      : back()->with(['status' => true, 'email' => $request->input('email')]);
  }

}
