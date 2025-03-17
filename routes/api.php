<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TanahController;
use App\Http\Controllers\sertifikatWakafController;
use App\Http\Controllers\ActivityLogController;
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

// Route::resource('products', ProductController::class);

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');




// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    //API TANAH
    Route::get('/tanah', [TanahController::class, 'index']);
    Route::get('/tanah/{id}', [TanahController::class, 'show']);
    Route::post('/tanah', [TanahController::class, 'store']);
    Route::put('/tanah/{id}', [TanahController::class, 'update']);
    Route::delete('/tanah/{id}', [TanahController::class, 'destroy']);

    // API Sertifikat Wakaf
    Route::get('/sertifikat', [sertifikatWakafController::class, 'index']);
    Route::get('/sertifikat/{id}', [sertifikatWakafController::class, 'show']);
    Route::post('/sertifikat', [sertifikatWakafController::class, 'store']);
    Route::put('/sertifikat/{id}', [sertifikatWakafController::class, 'update']);
    Route::delete('/sertifikat/{id}', [sertifikatWakafController::class, 'destroy']);

    // API ActivityLog
    Route::get('/log-tanah', [ActivityLogController::class, 'logTanah']);
    Route::get('/log-sertifikat', [ActivityLogController::class, 'logSertifikat']);
    Route::get('/log-status', [ActivityLogController::class, 'logStatus']);
    Route::get('/log-user/{userId}', [ActivityLogController::class, 'logByUser']);    
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

