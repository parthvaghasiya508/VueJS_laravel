<?php

namespace App\Listeners;

use App\Events\HotelRegistered;
use App\Models\Hotel;
use App\Services\RoomDB\RoomDBService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateRoomDBProperty
{

  /**
   * @var RoomDBService
   */
  protected $service;

  /**
   * @param RoomDBService $roomDBService
   */
  public function __construct(RoomDBService $roomDBService)
  {
    $this->service = $roomDBService;
  }

  /**
   * Handle the event.
   *
   * @param HotelRegistered $event
   * @return void
   */
  public function handle(HotelRegistered $event)
  {
    $data = $event->data;
    if (isset($data['skip']) && $data['skip']) return false;
    $hotel = Hotel::query()->find($data['hotel']);
    $response_create_property = $this->service->createProperty([
      'supplierPropertyId'  => $hotel->id,
      'name'                => $hotel->name,
      'alternativeName'     => $hotel->name,
      'code'                => sprintf("%s-%s", ucfirst((string)$hotel->name), $hotel->id),
      'status'              => 'active',
      'forTesting'          => false,
      'homeCurrencyCode'    => $data['currency_code'] ?: 'EUR',
    ]);
    if ($response_create_property['status'] === 201) {
      $response_body = $response_create_property['data'];

      $hotel->roomdb_id = $response_body['result']['id'];
      $hotel->save();
    } else {
      Log::error("Can't create property in RoomDB", ['response' => $response_create_property]);
    }
  }
}
