<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventaris extends Model
{
    use HasFactory;

    protected $table = 'inventaris';
    protected $primaryKey = 'id_inventaris';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_inventaris',
        'id_fasilitas',
        'nama_barang',
        'kode_barang',
        'satuan',
        'jumlah',
        'detail',
        'deskripsi',
        'kondisi',
        'catatan',
    ];

    protected $casts = [
        'jumlah' => 'integer',
    ];

    public function fasilitas()
    {
        return $this->belongsTo(Fasilitas::class, 'id_fasilitas', 'id_fasilitas');
    }
}