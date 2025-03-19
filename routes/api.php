<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TanahController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\sertifikatWakafController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;

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
    Route::put('/sertifikat/legalitas/{id}', [sertifikatWakafController::class, 'updateLegalitas']);
    Route::delete('/sertifikat/{id}', [sertifikatWakafController::class, 'destroy']);
    Route::get('/sertifikat/legalitas/{id}', [sertifikatWakafController::class, 'showLegalitas']);

    Route::get('/approvals', [ApprovalController::class, 'index']);
    Route::get('/approvals/{id}', [ApprovalController::class, 'show']);
    Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{id}/update/approve', [ApprovalController::class, 'approveUpdate']);
    Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject']);
    Route::post('/approvals/{id}/update/reject', [ApprovalController::class, 'rejectUpdate']);

    Route::get('/notifications', [NotificationController::class, 'index']); // Menampilkan notifikasi
    Route::post('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead']); // Menandai notifikasi sebagai sudah dibaca
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});