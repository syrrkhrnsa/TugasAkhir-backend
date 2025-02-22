<?php

namespace App\Http\Controllers;

use App\Models\Tanah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Ramsey\Uuid\Uuid;

class TanahController extends Controller
{
    // GET: Ambil semua data tanah
    public function index()
    {
        $tanahs = Tanah::all();
        return response()->json(["status" => "success", "data" => $tanahs], Response::HTTP_OK);
    }

    // GET: Ambil data tanah berdasarkan ID
    public function show($id)
    {
        $tanah = Tanah::find($id);
        if (!$tanah) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }
        return response()->json(["status" => "success", "data" => $tanah], Response::HTTP_OK);
    }

    // POST: Tambah data tanah baru
    // POST: Tambah data tanah baru
public function store(Request $request)
{
    try {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'NamaTanah' => 'required|string',
            'NamaWakif' => 'required|string',
            'lokasi' => 'required|string',
            'luasTanah' => 'required|string',
            'koordinatTanah' => 'required|json', // Pastikan input adalah string JSON valid
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi gagal",
                "errors" => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // Konversi koordinatTanah menjadi format yang dapat diproses PostgreSQL
        $geoJson = $request->input('koordinatTanah');

        // Periksa apakah GeoJSON valid
        $decodedGeoJson = json_decode($geoJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                "status" => "error",
                "message" => "Format koordinatTanah tidak valid, harus dalam format GeoJSON."
            ], Response::HTTP_BAD_REQUEST);
        }

        // Gunakan Eloquent untuk menyimpan data
        $tanah = Tanah::create([
            'id_tanah' => Uuid::uuid4()->toString(),
            'NamaTanah' => $request->NamaTanah,
            'NamaWakif' => $request->NamaWakif,
            'lokasi' => $request->lokasi,
            'luasTanah' => $request->luasTanah,
            'koordinatTanah' => DB::raw("ST_GeomFromGeoJSON('" . $geoJson . "')"),
        ]);

        return response()->json([
            "status" => "success",
            "message" => "Data tanah berhasil ditambahkan",
            "data" => $tanah
        ], Response::HTTP_CREATED);
    } catch (\Exception $e) {
        return response()->json([
            "status" => "error",
            "message" => "Terjadi kesalahan saat menyimpan data",
            "error" => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    // PUT: Update data tanah
    public function update(Request $request, $id)
    {
        $tanah = Tanah::find($id);
        if (!$tanah) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'NamaTanah' => 'sometimes|string',
            'NamaWakif' => 'sometimes|string',
            'lokasi' => 'sometimes|string',
            'luasTanah' => 'sometimes|string',
            'koordinatTanah' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(["status" => "error", "errors" => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $tanah->update($request->except('koordinatTanah'));
        if ($request->has('koordinatTanah')) {
            $tanah->update(["koordinatTanah" => DB::raw("ST_GeomFromGeoJSON('" . $request->koordinatTanah . "')")]);
        }

        return response()->json(["status" => "success","message" => "Data berhasil diupdate", "data" => $tanah], Response::HTTP_OK);
    }

    // DELETE: Hapus data tanah
    public function destroy($id)
    {
        $tanah = Tanah::find($id);
        if (!$tanah) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }

        $tanah->delete();
        return response()->json(["status" => "success", "message" => "Data berhasil dihapus"], Response::HTTP_OK);
    }
}