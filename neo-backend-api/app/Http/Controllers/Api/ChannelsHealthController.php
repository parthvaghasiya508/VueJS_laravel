<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ChannelsHealthController extends Controller {

  /**
   * Get contact persons with additional info
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    return $manager->getChannelsHealth();
  }
}
