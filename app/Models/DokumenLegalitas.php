<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DokumenLegalitas extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'dokumen_legalitas';
    protected $primaryKey = 'id_dokumen_legalitas';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sertifikat',
        'dokumen_legalitas'
    ];

    // Relasi ke Sertifikat
    public function sertifikat()
    {
        return $this->belongsTo(Sertifikat::class, 'id_sertifikat', 'id_sertifikat');
    }
}