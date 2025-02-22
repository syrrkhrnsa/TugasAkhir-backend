<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tanah extends Model
{
    use HasFactory;

    protected $table = 'tanahs';
    protected $primaryKey = 'id_tanah';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_tanah',
        'NamaTanah',
        'koordinatTanah',
        'NamaWakif',
        'lokasi',
        'luasTanah',
        'id_sertifikat' // Menambahkan kolom id_sertifikat
    ];

    // protected $casts = [
    //     'koordinatTanah' => 'string', // Mengubah geometri menjadi string JSON
    // ];

    // Relasi dengan Sertifikat
    public function sertifikat()
    {
        return $this->belongsTo(Sertifikat::class, 'id_sertifikat', 'id_sertifikat');
    }
}