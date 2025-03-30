<?php

namespace App\Http\Controllers;
use App\Models\Tanah;
use App\Models\Sertifikat;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardStats()
    {
        // Total tanah
        $totalTanah = Tanah::count();
        
        // Jumlah tanah berdasarkan jenis sertifikat
        $jenisSertifikat = Sertifikat::select('jenis_sertifikat', DB::raw('count(*) as total'))
            ->groupBy('jenis_sertifikat')
            ->get()
            ->pluck('total', 'jenis_sertifikat');
        
        return response()->json([
            'total_tanah' => $totalTanah,
            'jenis_sertifikat' => $jenisSertifikat
        ]);
    }
}