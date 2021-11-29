<?php

namespace App\Rules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class Password
 * @package App\Rules
 */
class Password implements Rule
{
  const REGEX = '/^(?=.*?[A-Z])(?=(.*[a-z]){1,})(?=(.*[\d]){1,})(?=(.*[\W_]){1,})(?!.*\s).{8,}$/';

  /**
   * @param string $attribute
   * @param mixed $value
   * @return bool
   */
  public function passes($attribute, $value)
  {
    return preg_match(self::REGEX, $value, $match);
  }

  /**
   * @return array|Application|Translator|string|null
   */
  public function message()
  {
    return __('validation.password');
  }
}
