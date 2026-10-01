<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KpiCategoryController;
use App\Http\Controllers\KpiIndicatorController;
use App\Http\Controllers\PositionKpiController;
use App\Http\Controllers\KpiSubmissionController;
use App\Http\Controllers\KpiEvidenceController;
use App\Http\Controllers\KpiReviewController;

Route::apiResource('roles', RoleController::class);
Route::apiResource('positions', PositionController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('kpi-categories', KpiCategoryController::class);
Route::apiResource('kpi-indicators', KpiIndicatorController::class);
Route::apiResource('position-kpis', PositionKpiController::class);
Route::apiResource('kpi-submissions', KpiSubmissionController::class);
Route::apiResource('kpi-evidences', KpiEvidenceController::class);
Route::apiResource('kpi-reviews', KpiReviewController::class);