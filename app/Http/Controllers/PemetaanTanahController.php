<?php

namespace App\Http\Controllers;

use App\Models\PemetaanTanah;
use App\Models\Tanah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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

    public function IndexAll()
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

    public function ShowDetail($id)
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

    public function getUserPemetaanTanah(Request $request, $userId)
    {
        try {
            // Validasi jika user ID tidak disediakan
            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User ID harus disediakan'
                ], 400);
            }
            
            // Ambil data pemetaan tanah oleh user dengan relasi tanah
            $pemetaanTanah = PemetaanTanah::where('id_user', $userId)
                ->with(['tanah'])
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $pemetaanTanah
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data pemetaan tanah',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getUserPemetaanTanahDetail($userId, $idPemetaanTanah)
    {
        try {
            // Validasi jika user ID tidak disediakan
            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User ID harus disediakan'
                ], 400);
            }
            
            // Ambil data pemetaan tanah beserta relasi tanah dan fasilitas
            $pemetaanTanah = PemetaanTanah::where('id_pemetaan_tanah', $idPemetaanTanah)
                ->where('id_user', $userId)
                ->with(['tanah', 'fasilitas'])
                ->firstOrFail();
            
            return response()->json([
                'status' => 'success',
                'data' => $pemetaanTanah
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pemetaan tanah tidak ditemukan atau tidak memiliki akses',
                'error' => $e->getMessage()
            ], 404);
        }
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

            // Calculate area from the geometry
            $geojson = json_decode($request->geometri, true);
            $area = $this->calculateAreaFromGeoJSON($geojson);

            $pemetaan = new PemetaanTanah([
                'id_pemetaan_tanah' => Str::uuid(),
                'id_tanah' => $tanahId,
                'id_user' => auth()->id(),
                'nama_pemetaan' => $request->nama_pemetaan,
                'keterangan' => $request->keterangan,
                'jenis_geometri' => $request->jenis_geometri,
                'luas_tanah' => $area, // Store calculated area
                'geometri' => $request->geometri
            ]);

            $pemetaan->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemetaan tanah berhasil dibuat',
                'data' => $pemetaan,
                'calculated_area' => $area,
                'original_area' => $tanah->luasTanah,
                'difference' => $tanah->luasTanah ? abs($tanah->luasTanah - $area) : null,
                'percentage_diff' => $tanah->luasTanah ? (abs($tanah->luasTanah - $area) / $tanah->luasTanah) * 100 : null
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store Pemetaan Error', [
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

    private function calculateAreaFromGeoJSON($geojson)
    {
        try {
            // For Polygon
            if ($geojson['type'] === 'Polygon') {
                $coordinates = $geojson['coordinates'][0]; // Get the outer ring
                $area = 0;
                $n = count($coordinates);
                
                if ($n > 2) {
                    for ($i = 0; $i < $n; $i++) {
                        $j = ($i + 1) % $n;
                        $xi = $coordinates[$i][0];
                        $yi = $coordinates[$i][1];
                        $xj = $coordinates[$j][0];
                        $yj = $coordinates[$j][1];
                        
                        $area += ($xi * $yj) - ($xj * $yi);
                    }
                    
                    $area = abs($area / 2);
                    
                    // Convert from degree² to m² (approximate)
                    // Note: This is a simplified calculation and may not be accurate for large areas
                    // For more accurate results, consider using a library like turf.js or PostGIS functions
                    $earthCircumference = 40075000; // meters
                    $degreesToMeters = $earthCircumference / 360;
                    $area = $area * pow($degreesToMeters, 2);
                    
                    return round($area, 2);
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            Log::error('Area calculation error', ['error' => $e->getMessage()]);
            return 0;
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