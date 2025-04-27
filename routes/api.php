<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TanahController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\sertifikatWakafController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PemetaanTanahController;
use App\Http\Controllers\PemetaanFasilitasController;
use App\Http\Controllers\FasilitasController;

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
Route::get('/tanah/public', [TanahController::class, 'publicIndex']);
Route::get('/sertifikat/public', [sertifikatWakafController::class, 'publicIndex']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/data/user', [UserController::class, 'index']);
    Route::get('/data/user/{id}', [UserController::class, 'show']);

    //API TANAH
    Route::get('/tanah', [TanahController::class, 'index']);
    Route::get('/tanah/{id}', [TanahController::class, 'show']);
    Route::post('/tanah', [TanahController::class, 'store']);
    Route::put('/tanah/{id}', [TanahController::class, 'update']);
    Route::delete('/tanah/{id}', [TanahController::class, 'destroy']);
    Route::put('/tanah/legalitas/{id}', [TanahController::class, 'updateLegalitas']);

       // API Sertifikat Wakaf
    Route::get('/sertifikat', [sertifikatWakafController::class, 'index']);
    Route::get('/sertifikat/{id}', [sertifikatWakafController::class, 'show']);
    Route::post('/sertifikat', [sertifikatWakafController::class, 'store']);
    Route::put('/sertifikat/{id}', [sertifikatWakafController::class, 'update']);
    
    Route::put('/sertifikat/jenissertifikat/{id}', [sertifikatWakafController::class, 'updateJenisSertifikat']);
    Route::put('/sertifikat/statuspengajuan/{id}', [sertifikatWakafController::class, 'updateStatusPengajuan']);
    Route::delete('/sertifikat/{id}', [sertifikatWakafController::class, 'destroy']);
    Route::get('/sertifikat/legalitas/{id}', [sertifikatWakafController::class, 'showLegalitas']);
    Route::get('/sertifikat/tanah/{id_tanah}', [sertifikatWakafController::class, 'getSertifikatByIdTanah']);

    
    Route::get('/approvals', [ApprovalController::class, 'index']);
    Route::get('/approvals/{id}', [ApprovalController::class, 'show']);
    Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{id}/update/approve', [ApprovalController::class, 'approveUpdate']);
    Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject']);
    Route::post('/approvals/{id}/update/reject', [ApprovalController::class, 'rejectUpdate']);
    Route::get('/approvals/type/{type}', [ApprovalController::class, 'getByType']);

    Route::get('/notifications', [NotificationController::class, 'index']); // Menampilkan notifikasi
    Route::post('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead']); // Menandai notifikasi sebagai sudah dibaca

    // API ActivityLog
    Route::get('/log-tanah', [ActivityLogController::class, 'logTanah']);
    Route::get('/log-sertifikat', [ActivityLogController::class, 'logSertifikat']);
    Route::get('/log-status', [ActivityLogController::class, 'logStatus']);
    Route::get('/log-user/{userId}', [ActivityLogController::class, 'logByUser']);
    Route::get('/log-tanah/{tanahId}', [ActivityLogController::class, 'logByTanahId']);
    Route::get('/log-sertifikat/{sertifikatId}', [ActivityLogController::class, 'logBySertifikatId']);
    Route::get('/log-sertifikat-by-tanah/{tanahId}', [ActivityLogController::class, 'logSertifikatByTanahId']);
    
    Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats']);

    Route::prefix('pemetaan')->group(function () {
        // Pemetaan Tanah
        Route::get('/tanah/{tanahId}', [PemetaanTanahController::class, 'index']);
        Route::post('/tanah/{tanahId}', [PemetaanTanahController::class, 'store']);
        Route::get('/tanah-detail/{id}', [PemetaanTanahController::class, 'show']);
        Route::put('/tanah/{id}', [PemetaanTanahController::class, 'update']);
        Route::delete('/tanah/{id}', [PemetaanTanahController::class, 'destroy']);
    
        // Pemetaan Fasilitas
        Route::get('/fasilitas/{pemetaanTanahId}', [PemetaanFasilitasController::class, 'index']);
        Route::post('/fasilitas/{pemetaanTanahId}', [PemetaanFasilitasController::class, 'store']);
        Route::get('/fasilitas-detail/{id}', [PemetaanFasilitasController::class, 'show']);
        Route::put('/fasilitas/{id}', [PemetaanFasilitasController::class, 'update']);
        Route::delete('/fasilitas/{id}', [PemetaanFasilitasController::class, 'destroy']);
    });

    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});