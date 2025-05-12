<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'catatan',
    ];

    public function pemetaanFasilitas(): BelongsTo
    {
        return $this->belongsTo(PemetaanFasilitas::class, 'id_pemetaan_fasilitas', 'id_pemetaan_fasilitas');
    }

    public function tanah(): BelongsTo
    {
        return $this->belongsTo(Tanah::class, 'id_tanah', 'id_tanah');
    }

    public function inventaris(): HasMany
    {
        return $this->hasMany(Inventaris::class, 'id_fasilitas', 'id_fasilitas');
    }

    public function filePendukung(): HasMany
    {
        return $this->hasMany(FilePendukungFasilitas::class, 'id_fasilitas', 'id_fasilitas');
    }
}