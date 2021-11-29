<?php

namespace App\Contracts;

interface MustFillDetails
{
  /**
   * @return bool
   */
  public function hasDetailsFilled();

  /**
   * @return bool
   */
  public function hasPersonalDetailsFilled();

  /**
   * @param array $personalDetails
   * @return void
   */
  public function updatePersonalDetails($personalDetails);
}
