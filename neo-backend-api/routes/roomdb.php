<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'RoomDB'], function () {
  Route::group(['prefix' => 'hotels'], function () {
    Route::post('', 'HotelController@create');
  });
});
