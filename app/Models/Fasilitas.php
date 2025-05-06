<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fasilitas extends Model
{
    use HasFactory;

    protected $table = 'fasilitas';
    protected $primaryKey = 'id_fasilitas';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_fasilitas',
        'id_pemetaan_fasilitas',
        'id_tanah',
        'file_360',
        'file_gambar',
        'file_pdf',
        'catatan',
    ];

    public function pemetaanFasilitas()
    {
        return $this->belongsTo(PemetaanFasilitas::class, 'id_pemetaan_fasilitas', 'id_pemetaan_fasilitas');
    }

    public function tanah()
    {
        return $this->belongsTo(Tanah::class, 'id_tanah', 'id_tanah');
    }

    public function inventaris()
    {
        return $this->hasMany(Inventaris::class, 'id_fasilitas', 'id_fasilitas');
    }

}