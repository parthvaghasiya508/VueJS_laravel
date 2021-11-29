<?php

use App\Lib\Cultuzz;
use App\Managers\CDManager;
use App\Managers\PMSManager;
use App\Models\Hotel;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Debug Routes
|--------------------------------------------------------------------------
*/

if (!function_exists('debug_response')) {
  function debug_response($res)
  {
    return response($res, 200, ['Content-Type' => (is_string($res) && trim($res)[0] === '<' ? 'text/xml' : 'application/json')]);
  }
}

if (!defined('DEBUG_HOTEL')) {
//  define('DEBUG_HOTEL', 58549);
//  define('DEBUG_HOTEL', 58828);
//  define('DEBUG_HOTEL', 56792);
//  define('DEBUG_HOTEL', 45522);
  define('DEBUG_HOTEL', 58571);
}

Route::get('/info', fn() => phpinfo());
Route::get('/req', fn(Request $request) => $request->all());
Route::get('/fail', fn() => User::query()->findOrFail(999));

Route::get('/contracts', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getAutoContractors(55075);
//  $res = $manager->debug(DEBUG_HOTEL)->getContract(55075, 1801);
  return debug_response($res);
});

Route::get('/contacts/get', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getContactPersons();
  return debug_response($res);
});

Route::get('/facilities', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getFacilities();
  return debug_response($res);
});

Route::get('/email', function () {
  $user = User::query()->find(1);
  app()->setLocale('ru');
//  return (new VerifyEmailNotification())->toMail($user);
  return (new ResetPasswordNotification('123', \App\Models\Group::find(13)))->toMail($user);
});

Route::get('/email-guest', function () {
  $user = User::query()->find(1);
//  app()->setLocale('ru');
//  return (new VerifyEmailNotification())->toMail($user);
  return (new \App\Notifications\InviteUserNotification('123', \App\Models\Group::find(13)))->toMail($user);
});

Route::get('/rooms-data', function (PMSManager $manager) {
//  $res = $manager->debug()->getRoomsData($user->profile, '2020-09-01');
  $res = $manager->debug(DEBUG_HOTEL)->getRoomsData('2020-09-02', null, true);
  return debug_response($res);
});

Route::get('/rooms', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getRoomTypesAndRatePlans(true, true);
  return debug_response($res);
});

Route::get('/products-count', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getProductsCount(true);
  return debug_response($res);
});

Route::get('/room/{id}', function ($id, PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getRoomType(['pid' => $id, '_debug' => true]);
  return debug_response($res);
});

Route::get('/activate', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->activateHotelBooking();
  return debug_response($res);
});

Route::get('/hotels/{id?}', function (PMSManager $manager, $id = null) {
  /** @var Hotel $hotel */
  $hotel = Hotel::query()->find($id ?? DEBUG_HOTEL);
  $res = $manager->debug()->setCredentials($hotel, $hotel->user)->getHotel($hotel, false);
  return debug_response($res);
});

Route::get('/create-plan', function (PMSManager $manager) {
  $payload = [
    'id'        => '145819',
//    'active' => false,
//    '_copy' => true,
//    '_delete' => true,
//    '_map' => '55075',
    'occupancy' => [
      'min' => 1,
      'std' => 3,
      'max' => 5,
    ],
    'minlos'    => 1,
    'maxlos'    => 999,
    'bgarant'   => 3,
    'meals'     => 3,
    'room'      => '81783',
    'cancels'   => ['1241'],
    'bdays'     => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    'adays'     => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    'ddays'     => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    'validity'  => [
      'from'  => Carbon::now(),
      'unlim' => true,
    ],
    'blockouts' => [
//      [
//        'from' => \Illuminate\Support\Carbon::now()->addMonth(),
//        'until' => \Illuminate\Support\Carbon::now()->addMonth()->addWeek(),
//      ]
    ],
    'langs'     => [
      'en' => [
        'name' => 'EN.Name',
        'desc' => 'EN.Desc',
      ],
      'de' => [
        'name' => 'DE.Name',
        'desc' => 'DE.Desc',
      ],
    ],
    'price'     => [
      'mode'    => 'standard',
      'stdcalc' => [
        'mode'      => 'surcharge',
        'surcharge' => [
          'value' => '15',
          'mode'  => 'percent',
        ],
      ],
      'calc'    => [
        'surcharge' => [
          'mode'  => 'percent',
          'value' => '10',
        ],
        'reduction' => [
          'mode'  => 'amount',
          'value' => '25',
        ],
        'children'  => [
          [
            'age'       => 10,
            'surcharge' => [
              'mode'  => 'percent',
              'value' => 8,
            ],
          ],
          [
            'age'       => 5,
            'surcharge' => [
              'mode'  => 'amount',
              'value' => 5,
            ],
          ],
        ],
      ],
//      'mode' => 'fixed',
//      'fixed' => '156',
    ],
  ];
  $res = $manager->debug(DEBUG_HOTEL)->modifyRatePlan($payload);
  return debug_response($res);
});

Route::get('/create-type', function (PMSManager $manager) {
  $payload = [
//    'id'        => '81873',
//    'rid'       => '100451',
    'id'       => '100471',
    'pid'        => '81893',
    'typecode' => '99',
    'active' => true,
//    '_copy' => true,
//    '_delete' => true,
    'occupancy' => [
      'min' => 1,
      'std' => 3,
      'max' => 5,
    ],
    'amount'       => 45,
    'validity'  => [
      'from'  => Carbon::now(),
      'until' => Carbon::now()->addYears(),
      'unlim' => false,
    ],
    'blockouts' => [
      [
        'from' => Carbon::now()->addMonth(),
        'until' => Carbon::now()->addMonth()->addWeek(),
      ]
    ],
    'langs'     => [
      'en' => [
        'name' => 'Default [en] 5',
        'desc' => 'Default desc [en] 5',
      ],
      'de' => [
        'name' => '',
        'desc' => '',
      ],
    ],
  ];
  $res = $manager->debug(DEBUG_HOTEL)->modifyRoomType($payload);
//  $res = $manager->debug()->activateRatePlan($user->profile, $payload);
  return debug_response($res);
});

Route::get('/plans', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getRatePlansWithExtraData();
  return debug_response($res);
});

Route::get('/bgarant', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getBGarant();
  return debug_response($res);
});

Route::get('/plan/{id}', function (PMSManager $manager, $id) {
  $res = $manager->debug(DEBUG_HOTEL)->getRatePlan(['id' => $id]);
  return debug_response($res);
});

Route::get('/rateplans', function (PMSManager $manager) {
//  $res = $manager->debug(DEBUG_HOTEL)->getRatePlan(['id' => 146502]);
  $res = $manager->debug(DEBUG_HOTEL)->getRatePlan(['ids' => [146503]]);
  return debug_response($res);
});

Route::get('/update-room-avail', function (PMSManager $manager) {
  $payload = [
    'rid'  => '100061',
    'day'  => '2020-05-17',
    'data' => [
      'avail' => '5',
    ],
  ];
  $res = $manager->debug(DEBUG_HOTEL)->updateRoomAvailability($payload);
  return debug_response($res);
});

Route::get('/update-room-data', function (PMSManager $manager) {
  $payload = [
    'rid'  => '100061',
    'day'  => '2020-05-17',
    'data' => [
      'price'  => '60',
      'minlos' => 1,
      'maxlos' => 99,
      'csale'  => false,
      'carr'   => true,
      'cdep'   => true,
    ],
  ];
  $res = $manager->debug(DEBUG_HOTEL)->updateRoomData($payload);
  return debug_response($res);
});

Route::get('/update-rooms', function (PMSManager $manager) {
  $payload = [
    'rooms'  => ['100061', '100063'],
    'from'   => '2020-06-20',
    'until'  => '2020-06-25',
    'days'   => ['Mon', 'Tue'],
    'fields' => [
      'avail' => 12,
      'price' => '8.00',
//      'grnt' => '3',
//      'osale' => true,
//      'carr' => false,
//      'cdep' => true,
//      'minlos' => '3',
//      'maxlos' => '5',
    ],
  ];
  $res = $manager->debug(DEBUG_HOTEL)->batchUpdateRooms($payload);
  return debug_response($res);
});

Route::get('/types', function () {
  return debug_response(Cultuzz::ROOM_TYPE_CODES);
});

Route::get('/reservations', function (PMSManager $manager) {
  $payload = [
    'type'  => 'StayPeriod',
    'from'  => '2020-08-01',
    'until' => '2020-09-01',
  ];
  $res = $manager->debug(DEBUG_HOTEL)->getReservations($payload);
  return debug_response($res);
});

Route::get('/cancel-reservation', function (PMSManager $manager) {
  $payload = [
    'id'     => '922075000',
    'reason' => 'Test cancellation "cancel"',
    'noshow' => true,
  ];
  $res = $manager->debug(DEBUG_HOTEL)->cancelReservation($payload);
  return debug_response($res);
});

Route::get('/channels', function (PMSManager $manager) {
//  $res = $manager->debug(DEBUG_HOTEL)->channelsList($user->profile);
  $res = $manager->debug(DEBUG_HOTEL)->channelPullMappings();
  return debug_response($res);
});

Route::get('/policies', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getPolicies();
  return debug_response($res);
});

Route::get('/channel/{id?}', function (PMSManager $manager, $id = null) {
  $payload = [
    '_debug' => true,
    '_fields' => true,
  ];
  if ($id) $payload += compact('id');
  $res = $manager->debug(DEBUG_HOTEL)->channelsList($payload);
  return debug_response($res);
});

Route::get('/systems-data', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->systemsData();
  return debug_response($res);
});

Route::get('/systems', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getSystems();
  return debug_response($res);
});

Route::get('/software', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getSoftware();
  return debug_response($res);
});

Route::get('/system', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getActivePMS();
  return debug_response($res);
});

Route::get('/system-get', function (PMSManager $manager) {
  $res = $manager->debug(DEBUG_HOTEL)->getActivePMS();
  return debug_response($res);
});

Route::get('/system-set', function (PMSManager $manager) {
//  $rules = [
//    'system'   => 'required_with::software',
//    'software' => 'required_with::system',
//  ];
  $payload = [
    'system'   => '1',
    'software' => '1',
  ];
  $res = $manager->debug(DEBUG_HOTEL)->setActivePMS($payload);
  return debug_response($res);
});

Route::get('/map/{id}', function (PMSManager $manager, $id) {
//  $payload = [
//    'id' => '55075',
//    'mode' => 'activate',
//  ];
  $res = $manager->debug(DEBUG_HOTEL)->channelInfoWithMappings($id, false);
  return debug_response($res);
});
Route::get('/map2', function (PMSManager $manager) {
  $payload = [
    'channel_id' => '54335',
    'hotel_key' => '1031642',
    '_debug' => true,
  ];
  $res = $manager->debug(DEBUG_HOTEL)->getChannelMappings($payload);
  return debug_response($res);
});
Route::get('/fields/{id?}', function (PMSManager $manager, $id = null) {
//  $payload = [
//    'id' => '55075',
//    'mode' => 'activate',
//  ];
  if (!$id) {
    $id = array_merge(config('cultuzz.enabled_channels'), [config('cultuzz.default_pull_channel')]);
  }
  $res = $manager->debug(DEBUG_HOTEL)->channelsFields($id, false);
  return debug_response($res);
});

Route::get('/channel/{id}/rates', function (PMSManager $manager, $id) {
//  $payload = [
//    'id' => '55075',
//    'mode' => 'activate',
//  ];
  $res = $manager->debug(DEBUG_HOTEL)->channelRatesList($id, false);
  return debug_response($res);
});

Route::get('/push-map', function (PMSManager $manager) {
  $payload = [
    'id' => '7563',
    'rooms' => [
      [
        'rid'    => '145315',
        'inv'    => false,
        'id'     => '11289823',
        'typeid' => '331907902',
        'mode'   => 1,
      ],
    ],
  ];
  $res = $manager->debug(DEBUG_HOTEL)->modifyPushMappings($payload);
  return debug_response($res);
});


Route::get('/channel/{id}/reg', function (PMSManager $manager, $id) {
  $payload = [
    'id' => $id,
    'mode' => 'update',
    'login' => '3319079',
    'period' => [
      'type' => 0,
      'number' => 3,
      'unit' => 'd',
      'until' => '2021-12-05',
    ],
  ];
  $res = $manager->debug(DEBUG_HOTEL)->modifyChannel($payload);
  return debug_response($res);
});

Route::get('/channel-rates/{id}', function (PMSManager $manager, $id) {
  $res = $manager->debug(DEBUG_HOTEL)->channelRatesList($id);
  return debug_response($res);
});

Route::get('/map-plan', function (PMSManager $manager) {
  $payload = [
    'id' => '55075',
    'rooms' => [
      '145815' => false,
      '145819' => true,
    ],
  ];
  $res = $manager->debug(DEBUG_HOTEL)->modifyPullMappings($payload);
  return debug_response($res);
});

Route::get('/meals/{id?}', function (PMSManager $manager, $id = null) {
  $payload = compact('id');
  $res = $manager->debug(DEBUG_HOTEL)->getMealPlans($payload);
  return debug_response($res);
});

Route::get('/nearby', function(PMSManager $manager) {
  $user = User::find(1);
  $payload = [];
  $res = $manager->debug()->getNearBy($user->profile, $payload);
  return debug_response($res);
});

Route::get('/update-nearby', function(PMSManager $manager) {
  $user = User::find(1);
  $payload = [
    'airports' => [
      [
        'code' => 'Cltz',
        'changed' => 1,
        'distance' => '10',
        'unit' => 'km',
      ],
      [
        'code' => 'Cltz',
        'changed' => 1,
        'distance' => '5',
        'unit' => 'km',
      ]
    ],
    'trains' => [
      [
        'code' => 'TRNS',
        'changed' => 1,
        'distance' => '10',
        'unit' => 'km',
      ],
      [
        'code' => 'CFR',
        'changed' => 1,
        'distance' => '5',
        'unit' => 'km',
      ]
    ],
    'publics' => [
      [
        'code' => 'bus',
        'changed' => 0,
        'distance' => '10',
        'unit' => 'km',
      ],
      [
        'code' => 'ferry',
        'changed' => 0,
        'distance' => '5',
        'unit' => 'km',
      ]
    ]
  ];
  $res = $manager->debug()->updateNearBy($payload);
  return debug_response($res);
});

Route::get('/invoices', function(CDManager $manager) {
  $user = User::find(1);
  // This is the id that cultdata requires?!
  // $clientId = $user->hotesl[0]->id;
  $payload = [
    'client_id' => 1394,
    'limit' => 1,
    'page' => 1
  ];
  $res = $manager->getInvoices($payload);
  return debug_response($res);
});

Route::get('/description', function(PMSManager $manager) {
  $user = User::find(1);
  $payload = [];
  $res = $manager->debug()->getDescriptions($user->profile, $payload);
  return debug_response($res);
});

Route::get('/update-description', function(PMSManager $manager) {
  $payload = [
    'descriptions' => [
      'description_long' => [
        'title' => 'Hotel Description - Long Version',
        'lang' => [
          'en' => 'Lorem ipsum dolor sit amet',
          'ro' => 'Ceva conținut cu mai multe limbi'
        ],
      ],
      'description_short' => [
        'title' => 'Hotel Description - Long Version',
        'lang' => [
          'en' => 'Lorem ipsum dolor sit amet'
        ],
      ],
      'liability' => [
        'title' => 'Hotel Description - Long Version',
        'lang' => [
          'en' => 'Lorem ipsum dolor sit amet'
        ],
      ],
      'insider_tips' => [
        'title' => 'Hotel Description - Long Version',
        'lang' => [
          'en' => 'Lorem ipsum dolor sit amet'
        ],
      ]
    ]
  ];
  $res = $manager->debug()->modifyHotel($payload, Hotel::find(58549));
  return debug_response($res);
});
