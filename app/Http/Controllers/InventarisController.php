<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InventarisController extends Controller
{
    public function index()
    {
        $inventaris = Inventaris::with(['fasilitas'])->get();
        return response()->json($inventaris);
    }

    public function showByFasilitas($id)
    {
        $inventaris = Inventaris::with(['fasilitas'])
            ->where('id_fasilitas', $id)
            ->get();
    
        return response()->json([
            "status" => "success",
            "data" => $inventaris
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_fasilitas' => 'required|uuid|exists:fasilitas,id_fasilitas',
            'nama_barang' => 'required|string|max:255',
            'kode_barang' => 'nullable|string|max:100',
            'satuan' => 'required|string|max:50',
            'jumlah' => 'required|integer|min:1',
            'detail' => 'nullable|string',
            'waktu_perolehan' => 'nullable|date',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat,hilang',
            'catatan' => 'nullable|string',
        ], [
            'id_fasilitas.required' => 'ID Fasilitas wajib diisi',
            'id_fasilitas.exists' => 'Fasilitas tidak ditemukan',
            'nama_barang.required' => 'Nama barang wajib diisi',
            'satuan.required' => 'Satuan wajib diisi',
            'jumlah.required' => 'Jumlah wajib diisi',
            'jumlah.min' => 'Jumlah minimal 1',
            'kondisi.required' => 'Kondisi wajib diisi',
            'kondisi.in' => 'Kondisi tidak valid',
            'waktu_perolehan.date' => 'Waktu perolehan harus berupa tanggal yang valid',
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

            $data = $request->all();
            $data['id_inventaris'] = Str::uuid();

            $inventaris = Inventaris::create($data);

            return response()->json([
                "status" => "success",
                "message" => "Inventaris berhasil dibuat",
                "data" => $inventaris
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating inventaris: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat menyimpan data",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $inventaris = Inventaris::with(['fasilitas'])->findOrFail($id);
        return response()->json($inventaris);
    }

    public function update(Request $request, $id)
    {
        $inventaris = Inventaris::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama_barang' => 'sometimes|string|max:255',
            'kode_barang' => 'nullable|string|max:100',
            'satuan' => 'sometimes|string|max:50',
            'jumlah' => 'sometimes|integer|min:1',
            'detail' => 'nullable|string',
            'waktu_perolehan' => 'nullable|date',
            'kondisi' => 'sometimes|in:baik,rusak_ringan,rusak_berat,hilang',
            'catatan' => 'nullable|string',
        ], [
            'nama_barang.required' => 'Nama barang wajib diisi',
            'satuan.required' => 'Satuan wajib diisi',
            'jumlah.required' => 'Jumlah wajib diisi',
            'jumlah.min' => 'Jumlah minimal 1',
            'kondisi.required' => 'Kondisi wajib diisi',
            'kondisi.in' => 'Kondisi tidak valid',
            'waktu_perolehan.date' => 'Waktu perolehan harus berupa tanggal yang valid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi gagal",
                "errors" => $validator->errors()
            ], 422);
        }

        try {
            $inventaris->update($request->all());

            return response()->json([
                "status" => "success",
                "message" => "Inventaris berhasil diperbarui",
                "data" => $inventaris
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating inventaris: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat memperbarui data",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $inventaris = Inventaris::findOrFail($id);

        try {
            $inventaris->delete();

            return response()->json([
                "status" => "success",
                "message" => "Inventaris berhasil dihapus"
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting inventaris: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat menghapus data",
                "error" => $e->getMessage()
            ], 500);
        }
    }
}