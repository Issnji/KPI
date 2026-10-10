<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KpiCategoriesController;
use App\Http\Controllers\KpiEvidencesController;
use App\Http\Controllers\KpiIndicatorController;
use App\Http\Controllers\KpiReviewController;
use App\Http\Controllers\KpiSubmissionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PositionKpisController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

Route::middleware(['auth:sanctum', 'role:admin'])
    ->get('tes/admin', fn () => response()->json(['ok' => 'halo admin']));

Route::middleware(['auth:sanctum', 'role:karyawan'])
    ->get('tes/karyawan', fn () => response()->json(['ok' => 'halo karyawan']));

    Route::middleware('role:admin')->group(function () {
        Route::apiResources([
            'roles'          => RoleController::class,
            'positions'      => PositionController::class,
            'users'          => UserController::class,
            'kpi-categories' => KpiCategoriesController::class,
            'kpi-indicators' => KpiIndicatorController::class,
            'position-kpis'  => PositionKpisController::class,
        ]);
    });

    Route::middleware('role:admin,manager')->group(function () {
        Route::apiResource('kpi-reviews', KpiReviewController::class);
    });

    Route::middleware('role:karyawan,manager,admin')->group(function () {
        Route::apiResource('kpi-submissions', KpiSubmissionController::class);
        Route::apiResource('kpi-evidences', KpiEvidencesController::class);
    });
});