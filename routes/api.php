<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication Endpoints
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
});

// Test Gemini Endpoint (without authentication for testing)
Route::post('/gemini/test', [GeminiController::class, 'test']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Endpoints
    Route::get('/user', [UserController::class, 'profile']);

    // Location Endpoints
    Route::post('/location', [LocationController::class, 'updateLocation']);

    // SOS Endpoints
    Route::prefix('sos')->group(function () {
        Route::post('/', [SosController::class, 'store']);
        Route::get('/available', [SosController::class, 'available']);
        Route::get('/my-requests', [SosController::class, 'myRequests']);
        Route::get('/history', [SosController::class, 'history']);
        Route::get('/{sosRequest}', [SosController::class, 'show'])->where('sosRequest', '[0-9]+');
        Route::post('/{sosRequest}/accept', [SosController::class, 'accept'])->where('sosRequest', '[0-9]+');
        Route::get('/{sosRequest}/communication', [SosController::class, 'communication'])->where('sosRequest', '[0-9]+');
    });

    // Hospital Endpoints
    Route::prefix('hospitals')->group(function () {
        Route::post('/nearest', [HospitalController::class, 'nearest']);
        Route::get('/sos-requests', [HospitalController::class, 'sosRequests']);
    });

    // Donation History Endpoints
    Route::prefix('donations')->group(function () {
        Route::get('/history', [DonationController::class, 'history']);
    });

    // Gemini AI Endpoints
    Route::prefix('gemini')->group(function () {
        Route::post('/search', [GeminiController::class, 'search']);
        Route::post('/analyze', [GeminiController::class, 'analyze']);
        Route::post('/report', [GeminiController::class, 'generateReport']);
    });
});
