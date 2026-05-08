<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\WorkshopController;
use Illuminate\Support\Facades\Route;

// Публичные маршруты
Route::prefix('v1')->group(function() {
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Публичный просмотр
Route::get('/workshops', [WorkshopController::class, 'index']);
Route::get('/workshops/{workshop}', [WorkshopController::class, 'show']);
Route::get('/venues', [VenueController::class, 'index']);
Route::get('/venues/{venue}', [VenueController::class, 'show']);

// Защищённые маршруты
Route::middleware('auth:sanctum')->group(function () {
    // Аутентификация
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Площадки (только админ)
    Route::post('/venues', [VenueController::class, 'store']);
    Route::patch('/venues/{venue}', [VenueController::class, 'update']);
    Route::delete('/venues/{venue}', [VenueController::class, 'destroy']);

    // Мастер-классы (организатор / админ)
    Route::post('/workshops', [WorkshopController::class, 'store']);
    Route::patch('/workshops/{workshop}', [WorkshopController::class, 'update']);
    Route::delete('/workshops/{workshop}', [WorkshopController::class, 'destroy']);

    // Регистрации
    Route::get('/registrations', [RegistrationController::class, 'index']);
    Route::post('/registrations', [RegistrationController::class, 'store']);
    Route::get('/registrations/{registration}', [RegistrationController::class, 'show']);
    Route::patch('/registrations/{registration}/status', [RegistrationController::class, 'updateStatus']);
    Route::delete('/registrations/{registration}', [RegistrationController::class, 'destroy']);
});
});
