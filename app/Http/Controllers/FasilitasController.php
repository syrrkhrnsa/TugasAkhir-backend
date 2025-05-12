<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\FilePendukungFasilitas;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FasilitasController extends Controller
{
    public function index()
    {
        $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah', 'filePendukung'])->get();
        return response()->json([
            "status" => "success",
            "data" => $fasilitas
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_pemetaan_fasilitas' => 'required|uuid|exists:pemetaan_fasilitas,id_pemetaan_fasilitas',
            'id_tanah' => 'required|uuid|exists:tanahs,id_tanah',
            'catatan' => 'nullable|string',
        ], [
            'id_pemetaan_fasilitas.required' => 'Pemetaan fasilitas wajib diisi',
            'id_tanah.required' => 'Tanah wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi gagal",
                "errors" => $validator->errors()
            ], 422);
        }

        try {
            // Check if fasilitas already exists for this pemetaan
            $existing = Fasilitas::where('id_pemetaan_fasilitas', $request->id_pemetaan_fasilitas)->first();
            
            if ($existing) {
                return response()->json([
                    "status" => "error",
                    "message" => "Fasilitas untuk pemetaan ini sudah ada",
                    "data" => $existing
                ], 422);
            }

            $fasilitas = Fasilitas::create([
                'id_fasilitas' => Str::uuid(),
                'id_pemetaan_fasilitas' => $request->id_pemetaan_fasilitas,
                'id_tanah' => $request->id_tanah,
                'catatan' => $request->catatan,
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Fasilitas berhasil dibuat",
                "data" => $fasilitas
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating fasilitas: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat menyimpan data",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah', 'filePendukung'])->findOrFail($id);
            
            return response()->json([
                "status" => "success",
                "data" => $fasilitas
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Fasilitas tidak ditemukan",
                "error" => $e->getMessage()
            ], 404);
        }
    }

    public function showByPemetaanFasilitas($id_pemetaan_fasilitas)
    {
        $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah', 'filePendukung'])
            ->where('id_pemetaan_fasilitas', $id_pemetaan_fasilitas)
            ->first();

        if (!$fasilitas) {
            return response()->json([
                "status" => "error",
                "message" => "Fasilitas tidak ditemukan"
            ], 404);
        }

        return response()->json([
            "status" => "success",
            "data" => $fasilitas
        ]);
    }

    public function publicIndex()
    {
        $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah', 'filePendukung'])
            ->whereHas('pemetaanFasilitas')
            ->get();

        return response()->json([
            "status" => "success",
            "data" => $fasilitas
        ]);
    }

    public function publicShow($id)
    {
        $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah', 'inventaris', 'filePendukung'])
            ->where('id_fasilitas', $id)
            ->first();
    
        if (!$fasilitas) {
            return response()->json([
                "status" => "error",
                "message" => "Fasilitas tidak ditemukan"
            ], 404);
        }
    
        return response()->json([
            "status" => "success",
            "data" => $fasilitas
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'catatan' => 'nullable|string',
            'id_tanah' => 'sometimes|required|uuid|exists:tanahs,id_tanah',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi gagal",
                "errors" => $validator->errors()
            ], 422);
        }

        try {
            $fasilitas = Fasilitas::where('id_fasilitas', $id)->first();

            if (!$fasilitas) {
                $fasilitas = Fasilitas::where('id_pemetaan_fasilitas', $id)->first();
                
                if (!$fasilitas) {
                    return response()->json([
                        "status" => "error",
                        "message" => "Fasilitas tidak ditemukan"
                    ], 404);
                }
            }

            $updateData = $request->only(['catatan']);
            
            if ($request->has('id_tanah')) {
                $updateData['id_tanah'] = $request->id_tanah;
            }

            $fasilitas->update($updateData);

            return response()->json([
                "status" => "success",
                "message" => "Fasilitas berhasil diperbarui",
                "data" => $fasilitas
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating fasilitas: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Gagal memperbarui fasilitas",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $fasilitas = Fasilitas::findOrFail($id);
            
            // Delete all related files first
            foreach ($fasilitas->filePendukung as $file) {
                Storage::disk('minio')->delete($file->path_file);
                $file->delete();
            }
            
            $fasilitas->delete();

            return response()->json([
                "status" => "success",
                "message" => "Fasilitas dan semua file terkait berhasil dihapus"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Gagal menghapus fasilitas",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function findByPemetaan($id_pemetaan_fasilitas)
    {
        $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah', 'filePendukung'])
            ->where('id_pemetaan_fasilitas', $id_pemetaan_fasilitas)
            ->first();
        
        if (!$fasilitas) {
            return response()->json([
                "status" => "error",
                "message" => "Fasilitas tidak ditemukan"
            ], 404);
        }
        
        return response()->json([
            "status" => "success",
            "data" => $fasilitas
        ]);
    }
}