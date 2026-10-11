<?php

use App\Http\Controllers\CheckOutController;
use App\Http\Controllers\RoomCheckInController;
use App\Http\Controllers\RoomOccupantController;
use Illuminate\Support\Facades\Route;

Route::post('/rooms/{room}/check-ins', [RoomCheckInController::class, 'store'])
    ->name('rooms.check-ins.store');

Route::get('/rooms/{room}/occupants', [RoomOccupantController::class, 'index'])
    ->name('rooms.occupants.index');

Route::post('/check-ins/{checkIn}/check-out', [CheckOutController::class, 'store'])
    ->name('check-ins.check-out.store');
