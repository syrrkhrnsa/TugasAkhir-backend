<?php

namespace App\Http\Controllers;

use App\Models\PemetaanFasilitas;
use App\Models\PemetaanTanah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PemetaanFasilitasController extends Controller
{
    public function publicIndex()
    {
        $fasilitas = PemetaanFasilitas::with('pemetaanTanah')->get();
        return response()->json([
            'status' => 'success',
            'data' => $fasilitas
        ]);
    }

    // Metode untuk melihat detail pemetaan fasilitas tertentu tanpa login
    public function publicShow($id)
    {
        try {
            $fasilitas = PemetaanFasilitas::with('pemetaanTanah')->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $fasilitas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pemetaan fasilitas tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // Metode untuk melihat semua fasilitas berdasarkan id pemetaan tanah tertentu tanpa login
    public function publicByPemetaanTanah($pemetaanTanahId)
    {
        $fasilitas = PemetaanFasilitas::where('id_pemetaan_tanah', $pemetaanTanahId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $fasilitas
        ]);
    }

    // Metode publik untuk mendapatkan semua fasilitas berdasarkan jenis
    public function publicByJenis($jenisFasilitas)
    {
        $fasilitas = PemetaanFasilitas::where('jenis_fasilitas', $jenisFasilitas)
                                      ->with('pemetaanTanah')
                                      ->get();
        return response()->json([
            'status' => 'success',
            'data' => $fasilitas
        ]);
    }
    
    public function index($pemetaanTanahId)
    {
        $fasilitas = PemetaanFasilitas::where('id_pemetaan_tanah', $pemetaanTanahId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $fasilitas
        ]);
    }

    public function store(Request $request, $pemetaanTanahId)
    {
        $validator = Validator::make($request->all(), [
            'jenis_fasilitas' => 'required|string|in:Bergerak,Tidak Bergerak',
            'kategori_fasilitas' => 'required|string|max:255',
            'nama_fasilitas' => 'required|string|max:255',
            'jenis_geometri' => 'required|string|in:POINT,LINESTRING,POLYGON',
            'geometri' => 'required|json',
            'keterangan' => 'nullable|string',
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
            $pemetaanTanah = PemetaanTanah::findOrFail($pemetaanTanahId);

            Log::debug('Pemetaan Fasilitas - Before Save', [
                'input' => $request->all(),
                'decoded_geojson' => json_decode($request->geometri, true),
                'json_valid' => json_last_error() === JSON_ERROR_NONE
            ]);

            $fasilitas = new PemetaanFasilitas([
                'id_pemetaan_fasilitas' => Str::uuid(),
                'id_pemetaan_tanah' => $pemetaanTanahId,
                'id_user' => auth()->id(),
                'jenis_fasilitas' => $request->jenis_fasilitas,
                'kategori_fasilitas' => $request->kategori_fasilitas,
                'nama_fasilitas' => $request->nama_fasilitas,
                'keterangan' => $request->keterangan,
                'jenis_geometri' => $request->jenis_geometri,
                'geometri' => $request->geometri
            ]);

            $fasilitas->save();

            DB::commit();

            // Debug setelah penyimpanan
            $debugData = [
                'saved_data' => $fasilitas->toArray(),
                'geometri_type' => gettype($fasilitas->geometri)
            ];

            // Tambahkan hex hanya jika geometri adalah string
            if (is_string($fasilitas->geometri)) {
                $debugData['geometri_hex'] = bin2hex($fasilitas->geometri);
            }

            Log::debug('Pemetaan Fasilitas - After Save', $debugData);

            return response()->json([
                'status' => 'success',
                'message' => 'Pemetaan fasilitas berhasil dibuat',
                'data' => $fasilitas->load('pemetaanTanah')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pemetaan Fasilitas - Store Error', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat pemetaan fasilitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $fasilitas = PemetaanFasilitas::with([
                'pemetaanTanah',
                'pemetaanTanah.tanah',
                'user'
            ])->findOrFail($id);
    
            return response()->json([
                'status' => 'success',
                'data' => $fasilitas
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching pemetaan fasilitas detail: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data pemetaan fasilitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'jenis_fasilitas' => 'sometimes|string|in:Bergerak,Tidak Bergerak',
            'kategori_fasilitas' => 'sometimes|string|max:255',
            'nama_fasilitas' => 'sometimes|string',
            'jenis_geometri' => 'sometimes|string|in:POINT,LINESTRING,POLYGON',
            'geometri' => 'sometimes|json',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fasilitas = PemetaanFasilitas::findOrFail($id);

            $updateData = [
                'jenis_fasilitas' => $request->jenis_fasilitas ?? $fasilitas->jenis_fasilitas,
                'kategori_fasilitas' => $request->kategori_fasilitas ?? $fasilitas->kategori_fasilitas,
                'nama_fasilitas' => $request->nama_fasilitas ?? $fasilitas->nama_fasilitas,
                'keterangan' => $request->keterangan ?? $fasilitas->keterangan,
            ];

            if ($request->has('geometri') && $request->has('jenis_geometri')) {
                $geojson = json_decode($request->geometri, true);
                $wkt = $this->geojsonToWkt($geojson, $request->jenis_geometri);
                $updateData['jenis_geometri'] = $request->jenis_geometri;
                $updateData['geometri'] = DB::raw("ST_GeomFromText('$wkt', 4326)");
            }

            $fasilitas->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Pemetaan fasilitas berhasil diperbarui',
                'data' => $fasilitas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui pemetaan fasilitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $fasilitas = PemetaanFasilitas::findOrFail($id);
            $fasilitas->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemetaan fasilitas berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus pemetaan fasilitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function geojsonToWkt($geojson, $type)
    {
        // Implementasi konversi GeoJSON ke WKT
        if ($type === 'POINT') {
            $coordinates = $geojson['coordinates'];
            return "POINT({$coordinates[0]} {$coordinates[1]})";
        } elseif ($type === 'LINESTRING') {
            $points = array_map(function ($coord) {
                return "{$coord[0]} {$coord[1]}";
            }, $geojson['coordinates']);
            $pointsStr = implode(', ', $points);
            return "LINESTRING($pointsStr)";
        } elseif ($type === 'POLYGON') {
            $coordinates = $geojson['coordinates'][0]; // Asumsi GeoJSON Polygon
            $points = array_map(function ($coord) {
                return "{$coord[0]} {$coord[1]}";
            }, $coordinates);
            $pointsStr = implode(', ', $points);
            return "POLYGON(($pointsStr))";
        }

        throw new \Exception("Jenis geometri $type belum didukung");
    }
}