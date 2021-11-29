<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\CDManager;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoicesController extends Controller {

  /**
   * Get client invoices
   *
   * @param Request $request
   * @param CDManager $manager
   *
   * @return array
   * @throws ValidationException
   */
  public function get(Request $request, CDManager $manager): array
  {
    $payload = $this->validate($request, [
      'page'  => 'required|numeric',
      'limit' => 'required|numeric',
    ]);
    /** @var Hotel $hotel */
    $hotel = session('hotel');
    $clientId = $hotel->id;
    return $manager->getInvoices($clientId, $payload['page'], $payload['limit']);
  }
}
