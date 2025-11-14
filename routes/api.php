<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\HoldController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('slots/availability', [AvailabilityController::class, 'getAvailableSlots']);

Route::post('slots/{id}/hold', [AvailabilityController::class, 'holdSlot']);

Route::post('holds/{id}/confirm', [HoldController::class, 'confirmHold']);

Route::delete('holds/{id}', [HoldController::class, 'cancelHold']);
