<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailUpdateRequest;
use App\Models\User;
use App\Notifications\ChangeEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChangeEmailController extends Controller {

  /**
   * Send an email with link for changing email address.
   *
   * @return bool[]
   */
  public function sendChangeLinkEmail(Request $request)
  {
    $user = $this->user($request);
    $group = $user->primaryGroup();
    $user->emailChanges()->delete();
    $token = Str::random(40);
    $user->emailChanges()->create([
      'email' => $user->email,
      'token' => $token,
    ]);
    $user->notify(new ChangeEmailNotification($user->email, $token, $group));
    return ['ok' => true];
  }

  /**
   * Update user's email address.
   *
   * @param \App\Http\Requests\EmailUpdateRequest $request
   *
   * @return bool[]
   */
  public function update(EmailUpdateRequest $request)
  {
    $user = User::firstWhere('email', $request->email);
    $user->update(['email' => $request->newEmail]);
    $user->emailChanges()->delete();
    Auth::guard()->login($user);
    return ['ok' => true];
  }

}
