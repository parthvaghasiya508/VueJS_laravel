<?php


namespace App\Managers;
use Illuminate\Support\Facades\Http;


class ApaleoManager
{

  /**
   * Get authorization details from Apaleo adapter
   *
   * @return mixed
   */
  public function getAccessDetails()
  {
    $response = Http::get(config('apaleo.adapter_url').'accessDetails');

    return json_decode($response->body());
  }

  public function getStatus($object_id)
  {
    $response = Http::get(config('apaleo.adapter_url').'status', [
      'objectId' => $object_id
    ]);

    return $response->body();
  }

  public function objectMap($object_id, $apaleoHotelKey)
  {
    $response = Http::post(config('apaleo.adapter_url').'objectMap', [
      'object_id' => $object_id,
      'pms_hotel_key' => $apaleoHotelKey,
      'pms_id' => config('apaleo.pms_id'),
    ]);

    return $response->body();
  }

  public function getObjectMap($object_id)
  {
    $response = Http::get(config('apaleo.adapter_url').'objectMap', [
      'cltzObjectId' => $object_id,
      'pmsId' => config('apaleo.pms_id'),
    ]);

    return $response->body();
  }

  public function getRatePlans($object_id, $pms_object_id)
  {
    $response = Http::get(config('apaleo.adapter_url').'rateplans', [
      'objectId' => $object_id,
      'pmsObjectId' => $pms_object_id,
    ]);

    return $response->body();
  }

  public function productMap($object_id, $products, $property_id)
  {
    $response = Http::post(config('apaleo.adapter_url').'productMap', [
      'object_id' => $object_id,
      'products' => $products,
      'property_id' => $property_id,
    ]);

    return $response->body();
  }
}
