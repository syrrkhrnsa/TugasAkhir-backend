<?php

namespace App\Http\Controllers;

use App\Models\PemetaanTanah;
use App\Models\Tanah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PemetaanTanahController extends Controller
{
    public function publicIndex()
    {
        $pemetaan = PemetaanTanah::with('tanah')->get();
        return response()->json([
            'status' => 'success',
            'data' => $pemetaan
        ]);
    }

    // Metode untuk melihat detail pemetaan tanah tertentu tanpa login
    public function publicShow($id)
    {
        try {
            $pemetaan = PemetaanTanah::with(['tanah', 'fasilitas'])->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $pemetaan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pemetaan tanah tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // Metode untuk melihat semua pemetaan tanah berdasarkan id tanah tertentu tanpa login
    public function publicByTanah($tanahId)
    {
        $pemetaan = PemetaanTanah::where('id_tanah', $tanahId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $pemetaan
        ]);
    }
    
    public function index($tanahId)
    {
        $pemetaan = PemetaanTanah::where('id_tanah', $tanahId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $pemetaan
        ]);
    }

    public function store(Request $request, $tanahId)
    {
        $validator = Validator::make($request->all(), [
            'nama_pemetaan' => 'required|string',
            'jenis_geometri' => 'required|string|in:POLYGON,MULTIPOLYGON',
            'geometri' => 'required|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $tanah = Tanah::findOrFail($tanahId);

            // Debug sebelum penyimpanan
            Log::debug('Before Save', [
                'geometri_input' => $request->geometri,
                'decoded' => json_decode($request->geometri, true)
            ]);

            $pemetaan = new PemetaanTanah([
                'id_pemetaan_tanah' => Str::uuid(),
                'id_tanah' => $tanahId,
                'id_user' => auth()->id(),
                'nama_pemetaan' => $request->nama_pemetaan,
                'keterangan' => $request->keterangan,
                'jenis_geometri' => $request->jenis_geometri,
                'geometri' => $request->geometri // Langsung gunakan string JSON
            ]);

            $pemetaan->save();

            DB::commit();

            // Debug setelah penyimpanan
            Log::debug('After Save', [
                'saved_geometri' => $pemetaan->geometri
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pemetaan tanah berhasil dibuat',
                'data' => $pemetaan
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store Pemetaan Error', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat pemetaan tanah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $pemetaan = PemetaanTanah::with('fasilitas')->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $pemetaan
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama_pemetaan' => 'sometimes|string',
            'jenis_geometri' => 'sometimes|string|in:POLYGON,MULTIPOLYGON',
            'geometri' => 'sometimes|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pemetaan = PemetaanTanah::findOrFail($id);

            $updateData = [
                'nama_pemetaan' => $request->nama_pemetaan ?? $pemetaan->nama_pemetaan,
                'keterangan' => $request->keterangan ?? $pemetaan->keterangan,
            ];

            if ($request->has('geometri') && $request->has('jenis_geometri')) {
                $geojson = json_decode($request->geometri, true);
                $wkt = $this->geojsonToWkt($geojson, $request->jenis_geometri);
                $updateData['jenis_geometri'] = $request->jenis_geometri;
                $updateData['geometri'] = DB::raw("ST_GeomFromText('$wkt', 4326)");
            }

            $pemetaan->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Pemetaan tanah berhasil diperbarui',
                'data' => $pemetaan
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui pemetaan tanah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $pemetaan = PemetaanTanah::findOrFail($id);
            $pemetaan->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemetaan tanah berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus pemetaan tanah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function geojsonToWkt($geojson, $type)
    {
        // Implementasi konversi GeoJSON ke WKT
        // Contoh sederhana untuk POLYGON
        if ($type === 'POLYGON') {
            $coordinates = $geojson['coordinates'][0]; // Asumsi GeoJSON Polygon
            $points = array_map(function ($coord) {
                return "{$coord[0]} {$coord[1]}";
            }, $coordinates);
            $pointsStr = implode(', ', $points);
            return "POLYGON(($pointsStr))";
        }

        // Tambahkan tipe geometri lainnya sesuai kebutuhan
        throw new \Exception("Jenis geometri $type belum didukung");
    }
}