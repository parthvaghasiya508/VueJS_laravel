<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Agent'], function () {

  Route::group(['prefix' => 'users'], function () {
    Route::get('', 'UsersController@list');
    Route::get('{agentUser}', 'UsersController@get');
    Route::put('{agentUser}', 'UsersController@update');
    Route::post('', 'UsersController@create');
    Route::post('{agentUser}/otl', 'UsersController@createOTLoginLink');

    Route::group(['prefix' => '{agentUser}/hotels'], function () {
      Route::get('', 'UsersController@listOfHotels');
//      Route::get('{hotel}', 'HotelsController@getOfUser');
      Route::post('', 'HotelsController@createOfUser');
//      Route::put('{hotel}', 'HotelsController@updateOfUser');
//      Route::patch('{hotel}', 'HotelsController@toggleStatusOfUser');

      Route::put('{agentHotel}', 'UsersController@addHotel');
      Route::delete('{agentHotel}', 'UsersController@deleteHotel');
      Route::put('', 'UsersController@setHotels');
      Route::delete('', 'UsersController@clearHotels');
    });
  });

  Route::group(['prefix' => 'hotels'], function () {
    Route::get('', 'HotelsController@list');
    Route::get('{agentHotel}', 'HotelsController@get');
    Route::post('', 'HotelsController@create');
    Route::put('{agentHotel}', 'HotelsController@update');
    Route::patch('{agentHotel}', 'HotelsController@toggleStatus');

    Route::group(['prefix' => '{agentHotel}/users'], function () {
      Route::get('', 'HotelsController@listOfUsers');
//      Route::get('{agentUser}', 'UsersController@show');

      Route::put('{agentUser}', 'HotelsController@addUser');
      Route::delete('{agentUser}', 'HotelsController@deleteUser');
      Route::put('', 'HotelsController@setUsers');
      Route::delete('', 'HotelsController@clearUsers');
    });
  });

});
