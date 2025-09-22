<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Report API routes for external system integration
Route::middleware('auth:sanctum')->prefix('v1/reports')->name('api.reports.')->group(function () {
    // Core report data endpoints
    Route::get('/sales', [App\Http\Controllers\Api\ReportApiController::class, 'salesData'])->name('sales');
    Route::get('/manifests', [App\Http\Controllers\Api\ReportApiController::class, 'manifestData'])->name('manifests');
    Route::get('/customers', [App\Http\Controllers\Api\ReportApiController::class, 'customerData'])->name('customers');
    Route::get('/financial-summary', [App\Http\Controllers\Api\ReportApiController::class, 'financialSummary'])->name('financial');
    
    // Dashboard and aggregated data
    Route::get('/dashboard-metrics', [App\Http\Controllers\Api\ReportApiController::class, 'dashboardMetrics'])->name('dashboard');
    
    // Configuration and options
    Route::get('/filter-options', [App\Http\Controllers\Api\ReportApiController::class, 'filterOptions'])->name('options');
});
