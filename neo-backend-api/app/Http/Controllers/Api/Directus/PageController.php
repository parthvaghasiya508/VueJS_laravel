<?php

namespace App\Http\Controllers\Api\Directus;

use App\Contracts\RestApiService;
use App\Http\Controllers\Controller;
use App\Services\Directus\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
  /**
   * @var RestApiService
   */
  private RestApiService $service;

  /**
   * PagesController constructor.
   *
   * @param PageService $service
   */
  public function __construct(PageService $service)
  {
    $this->service = $service;
  }

  /**
   * @param Request $request
   * @param int $id
   * @return JsonResponse
   */
  public function show(Request $request, int $id): JsonResponse
  {
    $result = $this->service->show($id, $request->all());

    return response()->json($result);
  }
}
