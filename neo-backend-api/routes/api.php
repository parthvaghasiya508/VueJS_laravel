<?php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['domain.check'], 'namespace' => 'Api'], function () {
  Route::get('info', 'GroupsController@domainInfo');
});

Route::group(['middleware' => ['auth:sanctum'], 'namespace' => 'Api'], function () {

  Route::group(['prefix' => 'user'], function () {
    Route::get('', 'UserController@get')->middleware('hotel.check:0');
    Route::put('', 'UserController@update')->middleware('permission:profile');
    Route::post('profile', 'UserController@setProfile');

    Route::group(['prefix' => 'hotels', 'middleware' => 'permission:hotels'], function () {
      Route::get('', 'HotelsController@list');
      Route::post('', 'HotelsController@create')->middleware('hotel.check:0');
    });

    Route::get('group', 'GroupsController@show')->middleware('hotel.check:0, permission:group');
    Route::put('group/{group}', 'GroupsController@update')->middleware('permission:group');

    Route::group(['middleware' => 'permission:stripe'], function () {
      Route::get('stripe', 'UserStripeController@get');
      Route::post('stripe', 'UserStripeController@store');
      Route::put('stripe', 'UserStripeController@update');
    });
  });

  Route::group(['prefix' => 'data'], function () {
    Route::get('countries', 'DataController@getCountries');
    Route::get('currencies', 'DataController@getCurrencies');
    Route::get('pages', 'DataController@getPages');
    Route::get('countries/{country_iso}/states', 'DataController@getStates');
    Route::get('property-types', 'DataController@getPropertyTypes');
    Route::get('languages', 'DataController@getLanguages');

  });

  Route::group(['middleware' => ['hotel.check']], function () {

    Route::group(['prefix' => 'user'], function () {
      Route::patch('setup', 'HotelsController@updateSetupStep');
    });

    Route::group(['middleware' => ['permission:booking'], 'prefix' => 'logs/hotels'], function () {
      Route::get('{hotel}/status', 'HotelsController@listStatusLogs');
    });

    Route::group(['prefix' => 'user/hotels'], function () {
      Route::get('{hotel}', 'HotelsController@get')->middleware('permission:hotels,masterdata');
      Route::get('{hotel}/pages', 'HotelsController@getPages')->middleware('permission:hotels,masterdata');
      Route::post('{hotel}', 'HotelsController@update')->middleware('permission:hotels,masterdata');
      Route::patch('{hotel}', 'HotelsController@toggleStatus')->middleware('permission:booking,hotels');
      Route::get('data/{hotel}', 'HotelsController@getPropertyData')->middleware('permission:hotels,masterdata');
    });

    Route::group(['middleware' => ['permission:hotels,masterdata'], 'prefix' => 'user/identifiers'], function () {
      Route::post('', 'IdentifierController@updatePropertyIdentifiers');
      Route::get('sources', 'IdentifierController@getIdentifierSources');
      Route::get('{propertyId}', 'IdentifierController@getPropertyIdentifiers');
    });

    Route::group(['middleware' => ['permission:reservations'], 'prefix' => 'reservations'], function () {
      Route::post('', 'ReservationsController@get');
      Route::patch('cancel', 'ReservationsController@cancel');
      Route::post('card-details', 'ReservationsController@getReservationCardIframe');
    });

    Route::group(['middleware' => ['permission:masterdata,description'], 'prefix' => 'description'], function () {
      Route::get('', 'DescriptionController@get');
      Route::put('', 'DescriptionController@update');
    });

    Route::group(['middleware' => ['permission:nearby'], 'prefix' => 'nearby'], function () {
      Route::get('', 'NearByController@get');
      Route::put('', 'NearByController@update');
    });

    Route::group(['middleware' => ['permission:facilities'], 'prefix' => 'facilities'], function () {
      Route::get('', 'FacilitiesController@get');
      Route::put('', 'FacilitiesController@update');
    });

    Route::group(['middleware' => ['permission:calendar'], 'prefix' => 'calendar'], function () {
      Route::get('rooms', 'CalendarController@getRooms');
      Route::post('rooms', 'CalendarController@getRoomsData');
      Route::put('rooms/avail', 'CalendarController@updateRoomAvail');
      Route::put('rooms/data', 'CalendarController@updateRoomData');
      Route::put('rooms/batch', 'CalendarController@batchUpdateRooms');
    });

    Route::group(['middleware' => ['permission:rateplans'], 'prefix' => 'plans'], function () {
      Route::get('', 'PlansController@get');
      Route::post('', 'PlansController@create');
      Route::put('{id}', 'PlansController@update');
      Route::post('{id}/duplicate', 'PlansController@duplicate');
      Route::delete('{id}', 'PlansController@destroy');
      Route::get('roomTypesWithRatePlan', 'PlansController@roomTypesWithRatePlan');
    });

    Route::group(['middleware' => ['permission:contactpersons'], 'prefix' => 'contacts'], function () {
      Route::get('', 'ContactsController@get');
      Route::post('', 'ContactsController@create');
      Route::put('{id}', 'ContactsController@update');
      Route::delete('{id}', 'ContactsController@destroy');
    });

    Route::group(['middleware' => ['permission:roomtypes'], 'prefix' => 'rooms'], function () {
      Route::get('', 'RoomsController@get');
      Route::post('', 'RoomsController@create');
      Route::put('{id}', 'RoomsController@update');
      Route::post('{id}', 'RoomsController@duplicate');
      Route::delete('{id}', 'RoomsController@destroy');
    });

    Route::group(['middleware' => ['permission:mealplans'], 'prefix' => 'mealplans'], function () {
      Route::get('', 'MealPlansController@get');
      Route::post('', 'MealPlansController@create');
      Route::put('{id}', 'MealPlansController@update');
      Route::post('{id}', 'MealPlansController@duplicate');
      Route::delete('{id}', 'MealPlansController@destroy');
    });

    Route::group(['middleware' => ['permission:photos'], 'prefix' => 'images'], function () {
      Route::get('', 'ImagesController@get');
      Route::post('', 'ImagesController@create');
      Route::delete('{image}', 'ImagesController@destroy');
      Route::put('{image}', 'ImagesController@update');
      Route::patch('{room}', 'ImagesController@reorder');
    });

    Route::group(['middleware' => ['permission:policies'], 'prefix' => 'policies'], function () {
      Route::get('', 'PoliciesController@get');
      Route::post('cancel', 'PoliciesController@createCancel');
      Route::put('cancel/{id}', 'PoliciesController@updateCancel');
  //    Route::post('cancel/{id}/duplicate', 'PoliciesController@duplicateCancel');
      Route::delete('cancel/{id}', 'PoliciesController@destroyCancel');
      Route::post('payment', 'PoliciesController@createPayment');
      Route::put('payment/{id}', 'PoliciesController@updatePayment');
  //    Route::post('payment/{id}/duplicate', 'PoliciesController@duplicatePayment');
      Route::delete('payment/{id}', 'PoliciesController@destroyPayment');
    });

    Route::group(['middleware' => ['permission:channels'], 'prefix' => 'channels'], function () {
      Route::get('', 'ChannelsController@list');
      Route::get('languages', 'ChannelsController@getLanguages');
      Route::get('{id}/fields', 'ChannelsController@getFields');
      Route::get('{id}', 'ChannelsController@get');
      Route::patch('{id}', 'ChannelsController@state');
      Route::patch('{id}/rate', 'ChannelsController@updateRatePlans');
      Route::put('{id}', 'ChannelsController@mappings');
      Route::group(['prefix' => '{id}/{mode}', 'where' => ['mode' => 'promo|contract']], function () {
        Route::post('', 'ChannelsController@createPromo');
        Route::put('{item}', 'ChannelsController@updatePromo');
        Route::delete('{item}', 'ChannelsController@deletePromo');
      });
      Route::get('{id}/hotel', 'ChannelsController@getHotel');
      Route::post('hotel', 'ChannelsController@createHotel');
      Route::get('{id}/colors', 'ChannelsController@getColors');
      Route::post('colors', 'ChannelsController@createColors');
      Route::put('{id}/colors', 'ChannelsController@updateColors');
      Route::get('{id}/email', 'ChannelsController@getEmail');
      Route::put('{id}/email', 'ChannelsController@updateEmail');
      Route::post('email', 'ChannelsController@createEmail');
    });

    Route::group(['middleware' => ['permission:channelshealth'], 'prefix' => 'channelshealth'], function () {
      Route::get('', 'ChannelsHealthController@get');
    });

    Route::group(['middleware' => ['permission:invoices'], 'prefix' => 'invoices'], function () {
      Route::post('', 'InvoicesController@get');
    });

    Route::group(['prefix' => 'reports'], function () {
      Route::get('recent', 'ReportsController@getRecent');
      Route::get('dashboard', 'ReportsController@getDashboard');
      Route::get('settings', 'ReportsController@getSettings');
    });

    Route::group(['middleware' => ['permission:systems'], 'prefix' => 'systems'], function () {
      Route::get('', 'SystemsController@all');
      Route::post('', 'SystemsController@state');
    });

    Route::group(['prefix' => 'widget'], function () {
      Route::put('', 'WidgetController@update');
      Route::put('update-visibility', 'WidgetController@updateWidgetVisibility');
      Route::put('update-position', 'WidgetController@updateWidgetPosition');
    });

    Route::group(['prefix' => 'directus'], function () {
      Route::group(['middleware' => ['permission:legal'], 'prefix' => 'pages'], function () {
        Route::get('/{id}', 'Directus\PageController@show');
      });
    });

    Route::group(['middleware' => ['permission:payments'] , 'prefix' => 'payments'], function() {
      Route::get('', 'PaymentsController@get');
      Route::post('', 'PaymentsController@store');
      Route::post('purchase', 'PaymentsController@purchase');
      Route::delete('{paymentMethod}', 'PaymentsController@delete');
      Route::get('payment-method/details/{paymentMethod}', 'PaymentsController@fetchPaymentMethod');
    });
  });

  /**
   * @OA\Info(title="Extranet auth API", version="0.1")
   */
  Route::namespace('Users')->group(function () {
    Route::post('users/invite', 'UsersController@inviteToJoinHotel')->middleware(['hotel.check:0', 'permission:users']);
    Route::delete('users/{user}/roles/{role}', 'UsersController@removeHotelAccess');
    Route::post('join/users/{user}/hotels/{hotel}/{hash}', 'UsersController@confirm')->name('users.hotel.join');
    Route::apiResource('roles', 'RolesController', ['except' => 'show'])->middleware(['hotel.check:0', 'permission:users']);
    Route::apiResource('users', 'UsersController', ['except' => 'show'])->middleware(['hotel.check:0', 'permission:users']);
  });

  Route::group(['prefix' => 'admin', 'middleware' => 'admin.check'], function () {
    Route::apiResource('groups', 'GroupsController');
    Route::group(['prefix' => 'groups/{group}/hotels'], function () {
      Route::post('import/{id}', 'HotelsController@import');
      Route::get('{hotel}', 'HotelsController@get');
      Route::post('{hotel}', 'HotelsController@update');
      Route::post('', 'HotelsController@create');
      Route::patch('{hotel}', 'HotelsController@toggleStatus');
    });
    Route::group(['prefix' => 'groups/{group}/domain'], function () {
      Route::post('check', 'GroupsController@checkDomain');
      Route::put('lock', 'GroupsController@lockDomain');
      Route::put('unlock', 'GroupsController@unlockDomain');
    });
    Route::group(['prefix' => 'groups/{group}/agent'], function () {
      Route::put('{agent}', 'GroupsController@assignAgent');
      Route::delete('', 'GroupsController@unassignAgent');
    });
    Route::post('groups/{group}/hotels', 'HotelsController@create');
  });

  Route::group(['prefix' => 'apaleo'], function (){
    Route::post('connect','ApaleoController@connect');
    Route::post('object-map','ApaleoController@objectMap');
    Route::get('object-map/{objectId}','ApaleoController@getObjectMap');
    Route::post('rate-plans','ApaleoController@ratePlans');
    Route::put('product-map','ApaleoController@productMap');
    Route::post('status','ApaleoController@getStatus');
    Route::get('revoke','ApaleoController@revoke');
  });

  Route::group(['prefix' => 'hostaway'], function (){
    Route::post('client-secret','HostawayController@clientSecret');
    Route::get('get-listing','HostawayController@getListing');
    Route::put('product-map','HostawayController@productMap');
    Route::get('get-product-mappings','HostawayController@getProductMappings');
    Route::get('get-mapped-accountId','HostawayController@getMappedAccountId');
  });

  Route::get('telephone-verify/{phone}','HotelsController@verifyPhone');
});
