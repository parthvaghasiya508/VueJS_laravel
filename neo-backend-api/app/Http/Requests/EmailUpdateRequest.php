<?php

namespace App\Http\Requests;

use App\Models\EmailChange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * @property string email
 * @property string token
 * @property string newEmail
 */
class EmailUpdateRequest extends FormRequest {

  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array
   */
  public function rules()
  {
    return [
      'email'    => ['required', 'exists:email_changes,email'],
      'token'    => [
        'required',
        function ($attribute, $value, $fail) {
          $token = EmailChange::where('email', request()->email)
            ->where('token', $value)
            ->first();

          if (!$token || $this->tokenExpired($token->created_at)) {
            $fail(__('mail.change.token_invalid'));
          }
        },
      ],
      'newEmail' => ['required', 'email', 'unique:users,email'],
    ];
  }

  /**
   * @param string $createdAt
   *
   * @return bool
   */
  private function tokenExpired($createdAt)
  {
    return Carbon::parse($createdAt)->addMinutes(config('auth.passwords.'.config('auth.defaults.passwords').'.expire'))->isPast();
  }
}
