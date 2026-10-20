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
use App\Http\Controllers\MyKpiController;
use App\Http\Controllers\MyKpiSubmissionController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    // FE karyawan
    Route::get('my-kpis', [MyKpiController::class, 'index'])
        ->middleware('role:karyawan,manager,admin');

    // Katalog (read) untuk admin & manager
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('kpi-categories', [KpiCategoriesController::class, 'index']);
        Route::get('positions/{position}/kpis', [PositionController::class, 'kpis']);
        Route::apiResource('kpi-reviews', KpiReviewController::class);
    });

    Route::middleware('role:admin')->group(function () {
        Route::apiResources([
            'roles'          => RoleController::class,
            'positions'      => PositionController::class,
            'users'          => UserController::class,
            'kpi-indicators' => KpiIndicatorController::class,
            'position-kpis'  => PositionKpisController::class,
        ]);
        // 'kpi-categories' dikeluarkan dari sini; index sudah di atas.
        // Daftarkan lagi store/update/destroy nanti dengan ->except('index').
    });

    Route::middleware('role:karyawan,manager,admin')->group(function () {
    // Alur karyawan
    Route::post('my-kpis/submissions', [MyKpiSubmissionController::class, 'store']);
    Route::post('kpi-submissions/{kpiSubmission}/evidences', [KpiEvidencesController::class, 'storeForSubmission']);

    // CRUD generik: batasi dulu, karena store/update/destroy-nya menerima user_id dari client
    Route::apiResource('kpi-submissions', KpiSubmissionController::class)->only(['index', 'show']);
    Route::apiResource('kpi-evidences', KpiEvidencesController::class);
});
});