<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AuthController;

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// Report API Routes (protected)
Route::prefix('reports')->middleware('auth:sanctum')->group(function () {
    // Preview report (fast, first page)
    Route::post('/preview', [ReportController::class, 'preview']);

    // Export management
    Route::get('/exports', [ReportController::class, 'list']);
    Route::post('/exports', [ReportController::class, 'export']);
    Route::get('/exports/{id}', [ReportController::class, 'status']);
    Route::delete('/exports/{id}', [ReportController::class, 'cancel']);
});
