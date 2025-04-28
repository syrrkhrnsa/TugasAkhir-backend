<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Fasilitas;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FasilitasController extends Controller
{
    public function index()
    {
        $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah'])->get();
        return response()->json($fasilitas);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_pemetaan_fasilitas' => 'required|uuid|exists:pemetaan_fasilitas,id_pemetaan_fasilitas',
            'id_tanah' => 'required|uuid|exists:tanahs,id_tanah',
            'file_360' => 'nullable|file|mimes:jpg,jpeg,png,mp4|max:5120',
            'file_gambar' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'file_pdf' => 'nullable|file|mimes:pdf|max:5120',
            'catatan' => 'nullable|string',
        ], [
            'id_pemetaan_fasilitas.required' => 'Pemetaan fasilitas wajib diisi',
            'id_tanah.required' => 'Tanah wajib diisi',
            'file_360.mimes' => 'File 360 derajat harus berupa JPG, JPEG, PNG, atau MP4',
            'file_gambar.mimes' => 'File gambar harus berupa JPG, JPEG, atau PNG',
            'file_pdf.mimes' => 'File PDF harus berupa PDF',
            'file_360.max' => 'Ukuran file maksimal 5MB',
            'file_gambar.max' => 'Ukuran gambar maksimal 5MB',
            'file_pdf.max' => 'Ukuran PDF maksimal 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi gagal",
                "errors" => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], 401);
            }

            $data = [
                'id_fasilitas' => Str::uuid(),
                'id_pemetaan_fasilitas' => $request->id_pemetaan_fasilitas,
                'id_tanah' => $request->id_tanah,
                'catatan' => $request->catatan,
            ];

            // Handle file uploads
            if ($request->hasFile('file_360')) {
                $path360 = $request->file('file_360')->store('fasilitas/file_360', 'public');
                $data['file_360'] = $path360;
            }

            if ($request->hasFile('file_gambar')) {
                $pathGambar = $request->file('file_gambar')->store('fasilitas/file_gambar', 'public');
                $data['file_gambar'] = $pathGambar;
            }

            if ($request->hasFile('file_pdf')) {
                $pathPdf = $request->file('file_pdf')->store('fasilitas/file_pdf', 'public');
                $data['file_pdf'] = $pathPdf;
            }

            $fasilitas = Fasilitas::create($data);

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
        $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah'])->findOrFail($id);
        return response()->json($fasilitas);
    }

    public function showByPemetaanFasilitas($id_pemetaan_fasilitas)
    {
        $fasilitas = Fasilitas::with(['pemetaanFasilitas', 'tanah'])
            ->where('id_pemetaan_fasilitas', $id_pemetaan_fasilitas)
            ->get();

        if ($fasilitas->isEmpty()) {
            return response()->json([
                "status" => "error",
                "message" => "Fasilitas dengan id_pemetaan_fasilitas tersebut tidak ditemukan."
            ], 404);
        }

        return response()->json([
            "status" => "success",
            "data" => $fasilitas
        ]);
    }


    public function update(Request $request, $id)
    {
        $fasilitas = Fasilitas::findOrFail($id);

        $data = $request->only(['catatan']);

        if ($request->hasFile('file_360')) {
            $data['file_360'] = $request->file('file_360')->store('uploads/fasilitas/360');
        }
        if ($request->hasFile('file_gambar')) {
            $data['file_gambar'] = $request->file('file_gambar')->store('uploads/fasilitas/gambar');
        }
        if ($request->hasFile('file_pdf')) {
            $data['file_pdf'] = $request->file('file_pdf')->store('uploads/fasilitas/pdf');
        }

        $fasilitas->update($data);

        return response()->json($fasilitas);
    }

    public function destroy($id)
    {
        $fasilitas = Fasilitas::findOrFail($id);
        $fasilitas->delete();

        return response()->json(['message' => 'Fasilitas deleted successfully']);
    }
}