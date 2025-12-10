<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportPresetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScheduledReportController;

// Authentication routes (public)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Report preview (fast, <500ms target)
    Route::post('/reports/preview', [ReportController::class, 'preview'])
        ->name('reports.preview');

    // Export management
    Route::post('/reports/exports', [ReportController::class, 'export'])
        ->middleware('throttle:10,1') // 10 exports per minute
        ->name('reports.export');

    Route::get('/reports/exports', [ReportController::class, 'index'])
        ->name('reports.index');

    // Admin endpoint - get all users' exports
    Route::get('/reports/exports/admin', [ReportController::class, 'adminIndex'])
        ->name('reports.admin-index');

    Route::get('/reports/exports/{id}', [ReportController::class, 'status'])
        ->name('reports.status');

    Route::delete('/reports/exports/{id}', [ReportController::class, 'destroy'])
        ->name('reports.delete');

    // Download with signed URL
    Route::get('/reports/download', [ReportController::class, 'download'])
        ->middleware('signed')
        ->name('reports.download');

    // Report presets (saved filters)
    Route::get('/reports/presets', [ReportPresetController::class, 'index'])
        ->name('presets.index');

    Route::post('/reports/presets', [ReportPresetController::class, 'store'])
        ->name('presets.store');

    Route::get('/reports/presets/{id}', [ReportPresetController::class, 'show'])
        ->name('presets.show');

    Route::put('/reports/presets/{id}', [ReportPresetController::class, 'update'])
        ->name('presets.update');

    Route::delete('/reports/presets/{id}', [ReportPresetController::class, 'destroy'])
        ->name('presets.delete');

    // Scheduled reports (recurring exports)
    Route::get('/reports/schedules', [ScheduledReportController::class, 'index'])
        ->name('schedules.index');

    Route::post('/reports/schedules', [ScheduledReportController::class, 'store'])
        ->name('schedules.store');

    Route::get('/reports/schedules/{schedule}', [ScheduledReportController::class, 'show'])
        ->name('schedules.show');

    Route::put('/reports/schedules/{schedule}', [ScheduledReportController::class, 'update'])
        ->name('schedules.update');

    Route::delete('/reports/schedules/{schedule}', [ScheduledReportController::class, 'destroy'])
        ->name('schedules.delete');

    Route::post('/reports/schedules/{schedule}/toggle', [ScheduledReportController::class, 'toggle'])
        ->name('schedules.toggle');

    Route::post('/reports/schedules/{schedule}/trigger', [ScheduledReportController::class, 'trigger'])
        ->name('schedules.trigger');
});
