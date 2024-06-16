<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', 'App\Http\Controllers\AuthController@register');
Route::post('/login', 'App\Http\Controllers\AuthController@login');
Route::post('/logout', 'App\Http\Controllers\AuthController@logout');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/stations', 'App\Http\Controllers\StationController@list');
    Route::get('/trips', 'App\Http\Controllers\TripController@list');

    Route::post('/trips/available', 'App\Http\Controllers\ReservationController@availableTrips');
    Route::post('/trips/reserve', 'App\Http\Controllers\ReservationController@reserve');

    Route::get('/reservations', 'App\Http\Controllers\ReservationController@list');
    Route::put('/reservations/{id}/cancel', 'App\Http\Controllers\ReservationController@cancelReservation');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
