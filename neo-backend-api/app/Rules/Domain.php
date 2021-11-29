<?php

namespace App\Rules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class Domain
 * @package App\Rules
 */
class Domain implements Rule {

  private $parts;

  public function __construct($parts = 2)
  {
    $this->parts = $parts;
  }

  /**
   * @param string $attribute
   * @param mixed $value
   *
   * @return bool
   */
  public function passes($attribute, $value): bool
  {
    $idn = idn_to_ascii($value);
    // do not allow IPs
    if (filter_var($idn, FILTER_VALIDATE_IP) !== false) {
      return false;
    }
    if (filter_var($idn, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
      return false;
    }
    return explode('.', $idn) >= $this->parts;
  }

  /**
   * @return array|Application|Translator|string|null
   */
  public function message()
  {
    return __('validation.domain');
  }
}
