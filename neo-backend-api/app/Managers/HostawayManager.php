<?php


namespace App\Managers;
use Illuminate\Support\Facades\Http;


class HostawayManager
{

  /**
   * Add/update client information that is needed for Hostaway API.
   */
  public function clientSecret($object_id, $account_id, $api_key)
  {
    $response = Http::post(config('hostaway.hostaway_url').'clientSecret', [
      'objectId' => $object_id,
      'accountID' => $account_id,
      'apikey' => $api_key
    ]);

    return $response->body();
  }

  /**
   * Get Hostaway Listings available for client.
   */
  public function getListing($object_id)
  {
    $response = Http::get(config('hostaway.hostaway_url').'getListings', [
      'objectId' => $object_id
    ]);

    return $response->body();
  }

  /**
   * Get product mappings for client.
   */
  public function getProductMappings($object_id)
  {
    $response = Http::get(config('hostaway.hostaway_url').'getProductMappings', [
      'objectId' => $object_id
    ]);

    return $response->body();
  }

  /**
   * Add/update mapping for CultSwitch and Hostaway products.
   */
  public function productMap($object_id, $products)
  {
    $response = Http::post(config('hostaway.hostaway_url').'productMap', [
                  'objectId' => $object_id,
                  'products' => $products,
                ]);

    return $response->body();
  }

  public function getMappedAccountId($object_id)
  {
    $response = Http::get(config('hostaway.hostaway_url').'syncStatus', [
      'objectId' => $object_id
    ]);

    return $response->body();
  }
}
