<?php

namespace App\Http\Requests;

use App\Rules\DateFormat;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserFormRequest extends FormRequest
{
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
    $optional = $this->partial ? 'sometimes' : 'required';
    return [
      'tel'              => "$optional|regex:/^\+\d{8,15}$/",
      'last_name'        => "$optional|string|max:255",
      'first_name'       => "$optional|string|max:255",
      'date_format'      => ["string", "max:12", new DateFormat, "nullable"],
      'number_format'    => "string|max:255|nullable",
      'default_language' => "string|max:2|nullable",
    ];
  }
}
