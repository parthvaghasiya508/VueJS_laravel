<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\PMSManager;
use App\Models\Hotel;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SystemsController extends Controller {

  /**
   * Get systems list
   *
   * @param PMSManager $manager
   *
   * @return Response|Collection|array
   */
  public function all(PMSManager $manager)
  {
    return $manager->systemsData();
  }

  /**
   * Change system state
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   * @throws ValidationException
   */
  public function state(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, [
      'system'   => 'required_with:software|numeric',
      'software' => 'required_with:system|numeric',
    ]);

    if ($payload) {
      $exists = $manager->getSoftware()->contains(fn ($s) => $s['id'] === $payload['software'] && $s['creator'] === $payload['system']);
      if (!$exists) throw new BadRequestHttpException('Unknown system:software pair');
    }

    $manager->setActivePMS($payload);
    return ['ok' => true];
  }
}
