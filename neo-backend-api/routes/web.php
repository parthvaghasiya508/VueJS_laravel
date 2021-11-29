<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth routes

Route::group(['namespace' => 'Auth'], function () {
  Route::post('login', 'LoginController@login');
  Route::post('logout', 'LoginController@logout')->name('logout');
  Route::post('register', 'RegisterController@register');
  Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail')->name('password.email');
  Route::post('password/reset', 'ResetPasswordController@reset')->name('password.reset');
  Route::post('email/resend', 'VerificationController@resend')->name('verification.resend');
  Route::post('email/send-email', 'ChangeEmailController@sendChangeLinkEmail')->name('email.send-email');
  Route::post('email/update', 'ChangeEmailController@update')->name('email.update');

  Route::post('email/verify/{id}/{hash}', 'VerificationController@verify')->name('verification.verify');
});

// Public routes

Route::group(['prefix' => '-'], function () {
  Route::get('gallery/{hotel}/{code}', 'Api\ImagesController@image')->where('code', '[A-Za-z0-9]{16}([A-Za-z0-9]{4})?');
  Route::get('groups/{code}', 'Api\ImagesController@groupImage')->where('code', '[A-Za-z0-9]{20}');
  Route::get('avatars/{code}', 'Api\ImagesController@avatarImage')->where('code', '[A-Za-z0-9]{20}');
});
