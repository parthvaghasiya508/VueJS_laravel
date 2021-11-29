<?php

namespace App\Http\Controllers\Api;

use App\Managers\ApaleoManager;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApaleoController extends Controller
{

  /**
   * Build Authorization url for oauth 2.0 with the help of getAccessDetails()
   *
   * @param Request $request, object_id
   * @return string
   */
  public function connect(Request $request, ApaleoManager $apaleoManager)
  {
    $accessDetails = $apaleoManager->getAccessDetails();

    $accessDetails->client_id;
    $accessDetails->redirect_uri;
    $accessDetails->scope;

    $cltzObjectId = $request->object_id;
    $userId = $request->user()->id;

    return config('apaleo.auth_url').$accessDetails->client_id."&redirect_uri=".$accessDetails->redirect_uri."&scope=".urlencode($accessDetails->scope)."&state=".$cltzObjectId.':'.$userId;
  }

  /**
   * Binding CultSwitch hotel to PMS hotel
   *
   * @param Request $request => object_id, apaleoHotelKey
   * @return string
   */
  public function objectMap(Request $request, ApaleoManager $apaleoManager)
  {
    return $apaleoManager->objectMap($request->object_id, $request->apaleoHotelKey);
  }

  /**
   * Get Cultswitch and PMS hotel mapping details.
   *
   * @param object_id
   * @return string
   */
  public function getObjectMap($objectId, ApaleoManager $apaleoManager)
  {
    return $apaleoManager->getObjectMap($objectId);
  }

  /**
   * Get rateplans from PMS
   *
   * @param Request $request => object_id
   * @return string
   */
  public function ratePlans(Request $request, ApaleoManager $apaleoManager)
  {
    $pms_object_id = $apaleoManager->getObjectMap($request->object_id);

    return $apaleoManager->getRatePlans($request->object_id, $pms_object_id);
  }

  /**
   * Provide access status details
   *
   * @param Request $request => object_id
   * @return string
   */
  public function getStatus(Request $request, ApaleoManager $apaleoManager)
  {
    return $apaleoManager->getStatus($request->object_id);
  }

  public function productMap(Request $request, ApaleoManager $apaleoManager)
  {
    $products = [];

    foreach ($request->selected as $key => $rs) {
      $product['cltz_element_id'] = 0;
      $product['cltz_product_id'] = $rs['product']['id'];
      $product['cltz_room_id'] = $rs['product']['room'];
      $product['partner_product_id'] = $rs['plan']['id'];
      $product['partner_room_id'] = $rs['plan']['unitGroup']['id'];
      array_push($products, $product);
    }

    return $apaleoManager->productMap($request->object_id, $products, $request->property_id);
  }

}

