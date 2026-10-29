<?php

use App\Http\Controllers\AdminKpiSubmissionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KpiCategoriesController;
use App\Http\Controllers\KpiEvidencesController;
use App\Http\Controllers\KpiIndicatorController;
use App\Http\Controllers\KpiReviewController;
use App\Http\Controllers\MyKpiController;
use App\Http\Controllers\MyKpiSubmissionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PositionKpisController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // FE karyawan
    Route::get('my-kpis', [MyKpiController::class, 'index'])
        ->middleware('role:karyawan,manager,admin');

    Route::middleware('role:karyawan,manager,admin')->group(function () {
        Route::post('my-kpis/submissions', [MyKpiSubmissionController::class, 'store']);
        Route::post('kpi-submissions/{kpiSubmission}/evidences', [KpiEvidencesController::class, 'storeForSubmission']);
    });

    // Katalog (read) + review untuk admin & manager
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('kpi-categories', [KpiCategoriesController::class, 'index']);
        Route::get('positions/{position}/kpis', [PositionController::class, 'kpis']);

        Route::prefix('reviews')->group(function () {
            // Alur utama: per laporan karyawan
            Route::get('reports', [KpiReviewController::class, 'reports']);
            Route::get('reports/{user}/{date}', [KpiReviewController::class, 'reportShow'])
                ->whereNumber('user')->where('date', '\d{4}-\d{2}-\d{2}');
            Route::post('reports/{user}/{date}', [KpiReviewController::class, 'reportDecide'])
                ->whereNumber('user')->where('date', '\d{4}-\d{2}-\d{2}');

            // Opsional: per indikator / bulk
            Route::get('queue', [KpiReviewController::class, 'queue']);
            Route::get('submissions/{kpiSubmission}', [KpiReviewController::class, 'show']);
            Route::post('submissions/{kpiSubmission}', [KpiReviewController::class, 'store']);
            Route::post('bulk', [KpiReviewController::class, 'bulk']);
        });
    });

    // Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Dropdown untuk form akun
        Route::get('roles', [RoleController::class, 'index']);
        Route::get('positions', [PositionController::class, 'index']);

        // Akun karyawan & store manager
        Route::apiResource('users', UserController::class);
        Route::patch('users/{user}/status', [UserController::class, 'updateStatus']);
        Route::put('users/{user}/password', [UserController::class, 'resetPassword']);

        // Kelola katalog indikator
        Route::apiResource('kpi-indicators', KpiIndicatorController::class);

        // Kelola indikator per jabatan (tambah/ubah/kurangi sekaligus, total bobot = 100)
        Route::put('positions/{position}/kpis', [PositionKpisController::class, 'sync']);

        // Capaian
        Route::get('kpi-submissions', [AdminKpiSubmissionController::class, 'index']);
        Route::get('kpi-submissions/{kpiSubmission}', [AdminKpiSubmissionController::class, 'show']);
    });
});