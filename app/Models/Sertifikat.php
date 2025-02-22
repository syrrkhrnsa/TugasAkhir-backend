<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Sertifikat extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sertifikats';
    protected $primaryKey = 'id_sertifikat';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sertifikat',
        'noSertifikat',
        'namaWakif',
        'lokasi',
        'luasTanah',
        'fasilitas',
        'status',
        'dokBastw',
        'dokAiw',
        'dokSw'
    ];

    // Relasi dengan Tanah
    public function tanah()
    {
        return $this->hasOne(Tanah::class, 'id_sertifikat', 'id_sertifikat');
    }
}