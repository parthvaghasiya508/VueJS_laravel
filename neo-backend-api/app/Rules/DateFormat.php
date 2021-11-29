<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class DateFormat implements Rule
{
  public const DATE_FORMAT_REGEX = '/^D{1,2}([ .\/-])M{1,3}\1(Y{2}|Y{4})$|^M{1,3}([ .\/-])D{1,2}\3(Y{2}|Y{4})$|^(Y{2}|Y{4})([ .\/-])M{1,3}\6D{1,2}$/';
  /**
   * Determine if the validation rule passes.
   *
   * @param  string  $attribute
   * @param  mixed  $value
   * @return bool
   */
  public function passes($attribute, $value)
  {
    return preg_match(self::DATE_FORMAT_REGEX, $value, $match);
  }

  /**
   * Get the validation error message.
   *
   * @return string
   */
  public function message()
  {
      return __('validation.date_formating');
  }
}
