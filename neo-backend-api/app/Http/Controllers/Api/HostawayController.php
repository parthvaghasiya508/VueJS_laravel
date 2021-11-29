<?php

namespace App\Http\Controllers\Api;

use App\Managers\HostawayManager;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HostawayController extends Controller
{
  public function clientSecret(Request $request, HostawayManager $hostawayManager)
  {
    return $hostawayManager->clientSecret($request->header('x-hotel-id'), $request->account_id, $request->api_key);
  }

  public function getListing(Request $request, HostawayManager $hostawayManager)
  {
    return $hostawayManager->getListing($request->header('x-hotel-id'));
  }

  public function getProductMappings(Request $request, HostawayManager $hostawayManager)
  {
    return $hostawayManager->getProductMappings($request->header('x-hotel-id'));
  }

  public function productMap(Request $request, HostawayManager $hostawayManager)
  {
    $products = [];

    foreach ($request->selected as $key => $rs) {
      $product['productId'] = $rs['product']['id'];
      $product['listingId'] = $rs['plan']['id'];
      array_push($products, $product);
    }

    return $hostawayManager->productMap($request->header('x-hotel-id'), $products);
  }

  public function getMappedAccountId(Request $request, HostawayManager $hostawayManager)
  {
    return $hostawayManager->getMappedAccountId($request->header('x-hotel-id'));
  }
}

