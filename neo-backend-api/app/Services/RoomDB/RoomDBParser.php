<?php

namespace App\Services\RoomDB;

use Symfony\Component\HttpFoundation\Response;


class RoomDBParser
{
  public function __construct(
  ) {
  }

  /**
   * @param $roomDbData
   * @return string|null
   */
  public function getPropertyCurrency($roomDbData) :?string
  {
    if ($roomDbData['status'] === Response::HTTP_OK) {
      if ($roomDbData['property']['result']['homeCurrency']) {
        return $roomDbData['property']['result']['homeCurrency']['code'];
      }
    }
    return null;
  }
}
