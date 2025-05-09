<?php

namespace App\Http\Controllers;
use App\Models\Tanah;
use App\Models\Sertifikat;
use App\Models\PemetaanFasilitas;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardStats()
    {
        // Count total tanah
        $totalTanah = Tanah::count();

        // Count certificate types
        $jenisSertifikat = Sertifikat::selectRaw('jenis_sertifikat, COUNT(*) as count')
            ->groupBy('jenis_sertifikat')
            ->pluck('count', 'jenis_sertifikat');

        // Count facility categories
        $kategoriFasilitas = PemetaanFasilitas::selectRaw('kategori_fasilitas, COUNT(*) as count')
            ->groupBy('kategori_fasilitas')
            ->pluck('count', 'kategori_fasilitas');

        // Count total facilities
        $totalFasilitas = PemetaanFasilitas::count();

        return response()->json([
            'total_tanah' => $totalTanah,
            'jenis_sertifikat' => $jenisSertifikat,
            'kategori_fasilitas' => $kategoriFasilitas,
            'total_fasilitas' => $totalFasilitas,
        ]);
    }
}