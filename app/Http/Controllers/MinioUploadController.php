<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MinioUploadController extends Controller
{
    // Upload file sertifikat
    public function upload(Request $request)
    {
        // Periksa apakah file di-upload
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded.'], 400);
        }

        $file = $request->file('file');
        $path = $file->store('uploads', 'minio'); // Gunakan disk 'minio'

        // Jika path tidak berhasil, kembalikan error
        if (!$path) {
            return response()->json(['error' => 'File could not be stored.'], 500);
        }

        // Dapatkan URL file yang sudah di-upload
        $url = Storage::disk('minio')->url($path); // Gunakan 'minio', bukan 's3'
        return response()->json(['url' => $url], 200);
    }

    // Ambil file sertifikat (download)
    public function getCertificate($filename)
    {
        // Tentukan path file yang sesuai dengan folder di MinIO
        $path = 'uploads/' . $filename;

        // Debugging: Cek path lengkap
        \Log::debug("Mencari file di MinIO: " . $path);

        // Periksa apakah file ada di MinIO
        if (!Storage::disk('minio')->exists($path)) {
            // Debugging: Log error jika file tidak ditemukan
            \Log::debug("File tidak ditemukan: " . $path);
            return response()->json(['error' => 'File not found.'], 404);
        }

        // Ambil file sebagai stream dan kirimkan ke client
        $stream = Storage::disk('minio')->readStream($path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"$filename\"",
        ]);
    }

    // Lihat URL sertifikat (view)
    public function viewCertificate($filename)
    {
        $path = 'uploads/' . $filename;

        // Debugging: Cek path lengkap
        \Log::debug("Mencari file untuk view: " . $path);

        // Periksa apakah file ada di MinIO
        if (!Storage::disk('minio')->exists($path)) {
            // Log jika file tidak ditemukan
            \Log::debug("File tidak ditemukan: " . $path);
            return response()->json(['error' => 'File not found.'], 404);
        }

        // Dapatkan URL untuk melihat file
        $url = Storage::disk('minio')->url($path); 
        return response()->json(['url' => $url], 200);
    }

    // Hapus file sertifikat
    public function deleteCertificate($filename)
    {
        // Tentukan path file yang sesuai dengan folder di MinIO
        $path = 'uploads/' . $filename;

        // Debugging: Cek path lengkap
        \Log::debug("Mencari file untuk hapus: " . $path);

        // Periksa apakah file ada di MinIO
        if (!Storage::disk('minio')->exists($path)) {
            // Log jika file tidak ditemukan
            \Log::debug("File tidak ditemukan: " . $path);
            return response()->json(['error' => 'File not found.'], 404);
        }

        // Hapus file dari MinIO
        Storage::disk('minio')->delete($path);

        // Kembalikan response jika file berhasil dihapus
        return response()->json(['message' => 'File deleted successfully.'], 200);
    }
}
