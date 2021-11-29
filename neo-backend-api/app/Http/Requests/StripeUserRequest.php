<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StripeUserRequest extends FormRequest
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
        return [
          'address' => 'sometimes',
          'description' => 'string|sometimes',
          'email' => 'email|sometimes',
          'metadata' => 'sometimes',
          'name' => 'string|sometimes',
          'phone' => 'numeric|sometimes',
          'shipping' => 'sometimes',
          'balance' => 'integer|sometimes',
          'coupon' => 'string|sometimes',
          'invoice_prefix' => 'string|min:3|max:12|sometimes',
          'invoice_settings' => 'sometimes',
          'next_invoice_sequence' => 'numeric|sometimes',
          'preferred_locales' => 'array|sometimes',
          'promotion_code' => 'numeric|sometimes',
          'tax_exempt' => 'sometimes|in:none,exempt,reverse',
        ];
    }
}
