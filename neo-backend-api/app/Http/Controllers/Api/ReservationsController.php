<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\PMSManager;
use App\Services\Reservation\CardIframeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReservationsController extends Controller {

  /**
   * Get reservations for specified interval
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|Collection|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    $data = $this->validate($request, [
      'from'   => 'required|date_format:Y-m-d',
      'until'  => 'required|date_format:Y-m-d',
      'search' => 'required|in:0,1,2,3',
      'currentPage' => 'required|numeric',
      'resultPerPage' => 'required|numeric',
    ]);
    $data['type'] = ['StayPeriod', 'ArrivalDate', 'DepartureDate', 'BookingEnteries'][$data['search']];
    return $manager->getReservations($data);
  }

  /**
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   */
  public function cancel(Request $request, PMSManager $manager)
  {
    $data = $this->validate($request, [
      'id'     => 'required|numeric',
      'reason' => 'required|string|max:100',
      'noshow' => 'required|boolean',
    ]);

    $reservation = $manager->getReservation($data);
    if (!$reservation) {
      throw new NotFoundHttpException('Reservation not found');
    }

    # check reservation type
    if (!$reservation['ok']) {
      throw new NotAcceptableHttpException('Reservation is not cancellable');
    }

    # for No-show, check CheckIn date
    # reservation can be marked as No-show only after CheckIn date is passed
    if ($data['noshow']) {
      $checkin_at = Carbon::parse($reservation['checkin_at'], 'UTC');
      $today = Carbon::now('UTC')->startOfDay();
      if ($today->isBefore($checkin_at)) {
        throw new NotAcceptableHttpException('Reservation is not cancellable now');
      }
    }

    # all set, cancel reservation
    $manager->cancelReservation($data);

    return [
      'id'           => $data['id'],
      'ok'           => false,
      'type'         => $data['noshow'] ? '18' : '15',
      'cancelled_at' => Carbon::now('UTC')->toIso8601ZuluString(),
    ];
  }

  public function getReservationCardIframe(
    Request $request,
    CardIframeService $cardIframeService
  ) {

    $payload = $this->validate($request, [
      'bookingId'   => 'required',
      'objectId'    => 'required',
    ]);
    $payload['userId'] = $this->user($request)->id;

    $response = Http::get($cardIframeService->getIframeUrl($payload));

    // remove style from iframe return
    return [
      'body' => preg_replace("/style='[^']+'/", "", $response->body())
    ];
  }
}
