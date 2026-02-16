<?php

use App\Http\Controllers\Api\ComponentController;
use App\Http\Controllers\Api\DependencyController;
use App\Http\Controllers\Api\DesignChangeController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

// ── Components ──────────────────────────────────────────────────
Route::prefix('components')->group(function () {
    Route::get('/',        [ComponentController::class, 'index']);
    Route::get('/graph',   [ComponentController::class, 'graph']);
    Route::post('/',       [ComponentController::class, 'store']);
    Route::get('/{component}',    [ComponentController::class, 'show']);
    Route::put('/{component}',    [ComponentController::class, 'update']);
});

// ── Dependencies ────────────────────────────────────────────────
Route::apiResource('dependencies', DependencyController::class)
    ->except(['show']);

// ── Design Changes ──────────────────────────────────────────────
Route::prefix('design-changes')->group(function () {
    Route::get('/',                          [DesignChangeController::class, 'index']);
    Route::post('/',                         [DesignChangeController::class, 'store']);
    Route::get('/{designChange}',            [DesignChangeController::class, 'show']);
    Route::patch('/{designChange}/status',   [DesignChangeController::class, 'updateStatus']);
    Route::post('/{designChange}/reanalyze', [DesignChangeController::class, 'reanalyze']);
});

// ── Notifications ───────────────────────────────────────────────
Route::prefix('notifications')->group(function () {
    Route::get('/',                       [NotificationController::class, 'index']);
    Route::get('/unread-count',           [NotificationController::class, 'unreadCount']);
    Route::patch('/{notification}/read',      [NotificationController::class, 'markRead']);
    Route::patch('/{notification}/actioned',  [NotificationController::class, 'markActioned']);
});