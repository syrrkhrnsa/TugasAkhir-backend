<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilePendukungFasilitas extends Model
{
    protected $table = 'file_pendukung_fasilitas';
    protected $primaryKey = 'id_file_pendukung';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_file_pendukung',
        'id_fasilitas',
        'jenis_file',
        'is_primary',
        'path_file',
        'nama_asli',
        'mime_type',
        'ukuran_file',
        'keterangan'
    ];

    public function fasilitas(): BelongsTo
    {
        return $this->belongsTo(Fasilitas::class, 'id_fasilitas', 'id_fasilitas');
    }
}