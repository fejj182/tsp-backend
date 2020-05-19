<?php

use Illuminate\Http\Request;

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

Route::get('/stations', 'StationController@enabled');

Route::post('/stations/nearest', 'StationController@nearest');

Route::post('/stations/connections', 'StationController@connections');

Route::get('/destinations', 'DestinationController@enabled');

Route::post('/destinations/connections', 'DestinationController@connections');

Route::post('/trip', 'TripController@create');

Route::get('/trip/{alias}', 'TripController@get');

Route::post('/trip/{alias}', 'TripController@update');

Route::post('/trip-destinations', 'TripControllerV2@create');

Route::get('/trip-destinations/{alias}', 'TripControllerV2@get');

Route::post('/trip-destinations/{alias}', 'TripControllerV2@update');