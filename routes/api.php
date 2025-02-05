<?php

use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/gps/reports', [ReportController::class, 'store']);

Route::get('/devices/{imei}/track', [DeviceController::class, 'track']);

Route::get('/devices', [DeviceController::class, 'list']);

Route::get('/devices/{imei}/latest', [DeviceController::class, 'latest']);
