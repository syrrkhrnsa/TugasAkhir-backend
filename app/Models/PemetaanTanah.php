<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Casts\GeometryCast;
use Illuminate\Support\Facades\DB;



class PemetaanTanah extends Model
{
    use HasFactory;


    protected $table = 'pemetaan_tanah';
    protected $primaryKey = 'id_pemetaan_tanah';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id_pemetaan_tanah',
        'id_tanah',
        'id_user',
        'nama_pemetaan',
        'keterangan',
        'jenis_geometri',
        'geometri',
        'luas_tanah',
    ];

    protected $casts = [
        'geometri' => GeometryCast::class
    ];



    public function tanah(): BelongsTo
    {
        return $this->belongsTo(Tanah::class, 'id_tanah');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function fasilitas(): HasMany
    {
        return $this->hasMany(PemetaanFasilitas::class, 'id_pemetaan_tanah');
    }
}
