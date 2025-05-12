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
use App\Http\Controllers\MinioUploadController;
use App\Http\Controllers\FasilitasController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\FasilitasFileController;

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

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::get('tanah/public', [TanahController::class, 'publicIndex']);
Route::get('tanah/public/{id}', [TanahController::class, 'publicShow']);
Route::get('tanah/search/public', [TanahController::class, 'publicSearch']);
Route::get('tanah/jenis/{jenisTanah}/public', [TanahController::class, 'publicByJenis']);
Route::get('tanah/pimpinan/{namaPimpinan}/public', [TanahController::class, 'publicByPimpinan']);

Route::get('/sertifikat/public', [sertifikatWakafController::class, 'publicIndex']);

Route::get('pemetaan/public', [PemetaanTanahController::class, 'publicIndex']);
Route::get('pemetaan/public/{id}', [PemetaanTanahController::class, 'publicShow']);
Route::get('tanah/{tanahId}/pemetaan/public', [PemetaanTanahController::class, 'publicByTanah']);

Route::get('fasilitas/public', [PemetaanFasilitasController::class, 'publicIndex']);
Route::get('fasilitas/public/{id}', [PemetaanFasilitasController::class, 'publicShow']);
Route::get('pemetaan/{pemetaanTanahId}/fasilitas/public', [PemetaanFasilitasController::class, 'publicByPemetaanTanah']);
Route::get('fasilitas/jenis/{jenisFasilitas}/public', [PemetaanFasilitasController::class, 'publicByJenis']);


Route::get('fasilitas/detail/public', [FasilitasController::class, 'publicIndex']);
Route::get('fasilitas/detail/publics/{id}', [FasilitasController::class, 'publicShow']);
Route::get('fasilitas/detail/public/{id}', [FasilitasController::class, 'publicShowDetail']);
Route::get('inventaris/fasilitas/{id}/public', [InventarisController::class, 'publicShowByFasilitas']);
Route::get('/datauser', [UserController::class, 'datauser']);
Route::get('inventaris/fasilitas/{id}/public/detail', [InventarisController::class, 'publicsShowByFasilitas']);

Route::post('/upload-minio', [MinioUploadController::class, 'upload']);
Route::get('/certificate/{filename}', [MinioUploadController::class, 'getCertificate']); // Untuk download
Route::get('/certificate/view/{filename}', [MinioUploadController::class, 'viewCertificate']); // Untuk lihat URL
Route::delete('/certificate/delete/{filename}', [MinioUploadController::class, 'deleteCertificate']); // Untuk delete

Route::get('/dokumen-legalitas/{id_dokumen_legalitas}/view', [sertifikatWakafController::class, 'viewDokumen']);
Route::get('/dokumen-legalitas/{id_dokumen_legalitas}/download', [sertifikatWakafController::class, 'downloadDokumen']);

Route::get('fasilitas/files/{id}/view', [FasilitasFileController::class, 'viewFile']);

// Public route untuk akses GeoTIFF
Route::get('/geotiff/{filename}', function ($filename) {
    $path = storage_path('app/geotiffs/'.urldecode($filename));
    
    if (!file_exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
    }
    
    // Validate it's actually a TIFF file
    $mime = mime_content_type($path);
    if (!in_array($mime, ['image/tiff', 'image/tif'])) {
        return response()->json(['error' => 'Invalid file type'], 400);
    }

    return response()->file($path, [
        'Content-Type' => 'image/tiff',
        'Content-Disposition' => 'inline',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
})->where('filename', '.*');

Route::get('/geotiff-list', function () {
    $files = glob(storage_path('app/geotiffs/*.{tif,tiff}'), GLOB_BRACE);
    $filenames = array_map('basename', $files);
    return response()->json($filenames);
});


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

    Route::get('/sertifikat/{id_sertifikat}/dokumen-list', [sertifikatWakafController::class, 'getDokumenList']);

    Route::post('/sertifikat/{id_sertifikat}/upload-dokumen', [sertifikatWakafController::class, 'uploadDokumen']);
    Route::delete('/dokumen-legalitas/{id_dokumen_legalitas}', [sertifikatWakafController::class, 'deleteDokumen']);
    Route::get('/sertifikat/{id_sertifikat}/dokumen', [sertifikatWakafController::class, 'getDokumenLegalitas']);

    Route::get('/approvals', [ApprovalController::class, 'index']);
    Route::get('/approvals/{id}', [ApprovalController::class, 'show']);
    Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{id}/update/approve', [ApprovalController::class, 'approveUpdate']);
    Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject']);
    Route::post('/approvals/{id}/update/reject', [ApprovalController::class, 'rejectUpdate']);
    Route::get('/approvals/type/{type}', [ApprovalController::class, 'getByType']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    // API ActivityLog
    Route::get('/log-tanah', [ActivityLogController::class, 'logTanah']);
    Route::get('/log-sertifikat', [ActivityLogController::class, 'logSertifikat']);
    Route::get('/log-status', [ActivityLogController::class, 'logStatus']);
    Route::get('/log-user/{userId}', [ActivityLogController::class, 'logByUser']);
    Route::get('/log-tanah/{tanahId}', [ActivityLogController::class, 'logByTanahId']);
    Route::get('/log-sertifikat/{sertifikatId}', [ActivityLogController::class, 'logBySertifikatId']);
    Route::get('/log-sertifikat-by-tanah/{tanahId}', [ActivityLogController::class, 'logSertifikatByTanahId']);
    Route::get('/logs/fasilitas', [ActivityLogController::class, 'logFasilitas']);
    Route::get('/logs/inventaris', [ActivityLogController::class, 'logInventaris']);
    Route::get('/logs/fasilitas/{fasilitasId}', [ActivityLogController::class, 'logByFasilitasId']);
    Route::get('/logs/inventaris/{inventarisId}', [ActivityLogController::class, 'logByInventarisId']);
    Route::get('/logs/pemetaan-fasilitas', [ActivityLogController::class, 'logPemetaanFasilitas']);
    Route::get('/logs/pemetaan-tanah', [ActivityLogController::class, 'logPemetaanTanah']);
    Route::get('/logs/pemetaan-fasilitas/{pemetaanFasilitasId}', [ActivityLogController::class, 'logByPemetaanFasilitasId']);
    Route::get('/logs/pemetaan-tanah/{pemetaanTanahId}', [ActivityLogController::class, 'logByPemetaanTanahId']);
    Route::get('/logs/pemetaan-fasilitas-by-tanah/{pemetaanTanahId}', [ActivityLogController::class, 'logPemetaanFasilitasByTanahId']);
    
    Route::get('/dashboard/stats', [DashboardController::class, 'getDashboardStats']);

    // Fasilitas Routes
    Route::prefix('fasilitas')->group(function () {
         Route::get('/', [FasilitasController::class, 'index']);
    Route::post('/', [FasilitasController::class, 'store']);
    Route::get('/{id}', [FasilitasController::class, 'show']);
    Route::put('/{id}', [FasilitasController::class, 'update']);
    Route::delete('/{id}', [FasilitasController::class, 'destroy']);
    Route::get('/pemetaan/{id_pemetaan_fasilitas}', [FasilitasController::class, 'showByPemetaanFasilitas']);
    Route::get('/by-pemetaan/{id_pemetaan_fasilitas}', [FasilitasController::class, 'findByPemetaan']);
    
    // File routes
    Route::post('/{id}/files', [FasilitasFileController::class, 'upload']);
    Route::get('/{id}/files', [FasilitasFileController::class, 'show']);
    Route::delete('/files/{id}', [FasilitasFileController::class, 'destroy']);;
    });

    // Inventaris Routes
    Route::prefix('inventaris')->group(function () {
        Route::get('/', [InventarisController::class, 'index']);
        Route::post('/', [InventarisController::class, 'store']);
        Route::get('/{id}', [InventarisController::class, 'show']);
        Route::put('/{id}', [InventarisController::class, 'update']);
        Route::delete('/{id}', [InventarisController::class, 'destroy']);
        Route::get('/fasilitas/{id}', [InventarisController::class, 'showByFasilitas']);
    });

    // Pemetaan Routes
    Route::prefix('pemetaan')->group(function () {
        // Pemetaan Tanah
        Route::get('/tanah', [PemetaanTanahController::class, 'IndexAll']);
        Route::get('/tanah/{tanahId}', [PemetaanTanahController::class, 'index']);
        Route::post('/tanah/{tanahId}', [PemetaanTanahController::class, 'store']);
        Route::get('/tanah-detail/{id}', [PemetaanTanahController::class, 'show']);
        Route::put('/tanah/{id}', [PemetaanTanahController::class, 'update']);
        Route::delete('/tanah/{id}', [PemetaanTanahController::class, 'destroy']);
        Route::get('/user/pemetaan-tanah/{userId}', [PemetaanTanahController::class, 'getUserPemetaanTanah']);
        Route::get('/user/pemetaan-tanah/{userId}/{idPemetaanTanah}', [PemetaanTanahController::class, 'getUserPemetaanTanahDetail']);
        
            // Pemetaan Fasilitas
        Route::get('/fasilitas', [PemetaanFasilitasController::class, 'indexAll']);
        Route::get('/fasilitas/{pemetaanTanahId}', [PemetaanFasilitasController::class, 'index']);
        Route::post('/fasilitas/{pemetaanTanahId}', [PemetaanFasilitasController::class, 'store']);
        Route::get('/fasilitas-detail/{id}', [PemetaanFasilitasController::class, 'show']);
        Route::put('/fasilitas/{id}', [PemetaanFasilitasController::class, 'update']);
        Route::delete('/fasilitas/{id}', [PemetaanFasilitasController::class, 'destroy']);
        Route::get('/user/pemetaan-fasilitas/{userId}', [PemetaanFasilitasController::class, 'getUserPemetaanFasilitas']);
        Route::get('/user/pemetaan-fasilitas/{userId}/{idPemetaanFasilitas}', [PemetaanFasilitasController::class, 'getUserPemetaanFasilitasDetail']);
    });


    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Temporarily add this route to test MinIO connection
Route::get('/test-minio', function() {
    try {
        Storage::disk('minio')->put('test.txt', 'Test content');
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'config' => config('filesystems.disks.minio')
        ], 500);
    }
});