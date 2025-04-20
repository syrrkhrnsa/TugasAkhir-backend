<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Casts\GeometryCast;
use Illuminate\Support\Facades\DB;


class PemetaanFasilitas extends Model
{
    protected $table = 'pemetaan_fasilitas';
    protected $primaryKey = 'id_pemetaan_fasilitas';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_pemetaan_fasilitas',
        'id_pemetaan_tanah',
        'id_user',
        'jenis_fasilitas',
        'nama_fasilitas',
        'keterangan',
        'jenis_geometri',
        'geometri'
    ];

    protected $casts = [
        'geometri' => GeometryCast::class
    ];

    public function pemetaanTanah(): BelongsTo
    {
        return $this->belongsTo(PemetaanTanah::class, 'id_pemetaan_tanah');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}