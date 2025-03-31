<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Middleware\AdminApiKeyAuth;

// Маршруты, требующие API ключ администратора
Route::middleware([AdminApiKeyAuth::class])->group(function () {
    // Управление пользователями
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

// Маршруты аутентификации для обычных пользователей
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Защищенные маршруты для авторизованных пользователей
Route::middleware(ApiAuthenticate::class)->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::put('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});

// Маршрут для деплоя
Route::post('/deploy', [DeployController::class, 'deploy']); 