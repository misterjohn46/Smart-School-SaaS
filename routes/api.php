<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\IotAttendanceController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public routes (tidak perlu login)
Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api-register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

    // Endpoint IoT Mikrokontroler RFID (ESP32 via WiFi)
    Route::middleware('throttle:iot-device')->group(function () {
        Route::post('/attendance/tap-rfid', [IotAttendanceController::class, 'tapRfid']);
        Route::get('/attendance/ping', [IotAttendanceController::class, 'ping']);
    });

    // Protected routes (perlu login + email terverifikasi).
    // Tautan verifikasi dikirim lewat email saat registrasi dan dibuka di browser;
    // token yang sudah diterbitkan tetap berlaku setelah verifikasi selesai.
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => UserResource::make($request->user()->load('roles'))->resolve(),
                'message' => 'User retrieved successfully',
            ]);
        });
    });
});
