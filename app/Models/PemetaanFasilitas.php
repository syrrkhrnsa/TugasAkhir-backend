<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\GeometryCast;
use Illuminate\Support\Facades\DB;

class PemetaanFasilitas extends Model
{
    use HasFactory;


    protected $table = 'pemetaan_fasilitas';
    protected $primaryKey = 'id_pemetaan_fasilitas';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_pemetaan_fasilitas',
        'id_pemetaan_tanah',
        'id_user',
        'jenis_fasilitas',
        'kategori_fasilitas', // Added new field
        'nama_fasilitas',
        'keterangan',
        'jenis_geometri',
        'geometri'
    ];

    protected $casts = [
        'geometri' => GeometryCast::class,
        'jenis_fasilitas' => 'string' // Enum will be cast to string
    ];

    // Optionally, you can add constants for jenis_fasilitas
    const JENIS_BERGERAK = 'Bergerak';
    const JENIS_TIDAK_BERGERAK = 'Tidak Bergerak';

    public function pemetaanTanah(): BelongsTo
    {
        return $this->belongsTo(PemetaanTanah::class, 'id_pemetaan_tanah');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
