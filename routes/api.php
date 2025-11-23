<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HospitalRequestController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    Route::get('/donations', [UserController::class, 'donationHistory']);
    Route::get('/available-sos-cases', [UserController::class, 'getAvailableSosCases']);
    Route::get('/new-cases-count', [UserController::class, 'getNewCasesCount']);
    Route::post('/sos', [SosController::class, 'store']);
    Route::post('/location', [SosController::class, 'updateLocation']);

    // Hospital Requests Routes
    Route::prefix('hospital-requests')->group(function () {
        // Patient/Donor routes
        Route::post('/', [HospitalRequestController::class, 'store']);
        Route::post('/nearest', [HospitalRequestController::class, 'sendToNearest']);
        Route::post('/both', [HospitalRequestController::class, 'sendBothRequests']);
        Route::get('/find-nearest', [HospitalRequestController::class, 'findNearest']);
        Route::get('/my-requests', [HospitalRequestController::class, 'myRequests']);
        Route::get('/available-cases', [HospitalRequestController::class, 'getAvailableCases']);
        Route::get('/new-count', [HospitalRequestController::class, 'getNewCasesCount']);

        // Hospital routes
        Route::get('/', [HospitalRequestController::class, 'index']);
        Route::post('/{id}/approve', [HospitalRequestController::class, 'approveOrReject']);
    });
});
