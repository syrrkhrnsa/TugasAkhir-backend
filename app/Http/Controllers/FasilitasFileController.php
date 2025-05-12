<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\FilePendukungFasilitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class FasilitasFileController extends Controller
{
    public function upload(Request $request, string $idFasilitas)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf,mp4,mov,avi|max:15360',
            'jenis_file' => 'required|in:360,gambar,dokumen',
            'keterangan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if (!$request->hasFile('files')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada file yang diupload'
                ], 400);
            }

            $uploadedFiles = [];
            $files = $request->file('files');
            
            foreach ($files as $file) {
                if (!$file->isValid()) {
                    continue;
                }

                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid() . '.' . $extension;
                $path = "fasilitas/{$request->jenis_file}/{$idFasilitas}/{$filename}";
                
                Storage::disk('minio')->put($path, file_get_contents($file));

                $fileRecord = FilePendukungFasilitas::create([
                    'id_file_pendukung' => Str::uuid(),
                    'id_fasilitas' => $idFasilitas,
                    'jenis_file' => $request->jenis_file,
                    'path_file' => $path,
                    'nama_asli' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'ukuran_file' => $file->getSize(),
                    'keterangan' => $request->keterangan ?? null
                ]);

                $uploadedFiles[] = $fileRecord;
            }

            return response()->json([
                'status' => 'success',
                'message' => count($uploadedFiles) . ' file berhasil diupload',
                'data' => $uploadedFiles
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $idFasilitas)
    {
        try {
            $files = FilePendukungFasilitas::where('id_fasilitas', $idFasilitas)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $idFile)
    {
        try {
            $file = FilePendukungFasilitas::findOrFail($idFile);
            Storage::disk('minio')->delete($file->path_file);
            $file->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'File berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewFile($id)
{
    try {
        $file = FilePendukungFasilitas::findOrFail($id);
        
        if (!Storage::disk('minio')->exists($file->path_file)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File not found in storage'
            ], 404);
        }

        $fileContents = Storage::disk('minio')->get($file->path_file);
        
        return response($fileContents)
            ->header('Content-Type', $file->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $file->nama_asli . '"');
            
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'File record not found'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to retrieve file',
            'error' => $e->getMessage()
        ], 500);
    }
}
}