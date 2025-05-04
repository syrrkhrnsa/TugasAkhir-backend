<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Casts\GeometryCast;

class Tanah extends Model
{
    use HasFactory;

    protected $table = 'tanahs';
    protected $primaryKey = 'id_tanah';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_tanah',
        'NamaPimpinanJamaah',
        'NamaWakif',
        'lokasi',
        'luasTanah',
        'legalitas',
        'status',
        'user_id',

        'jenis_tanah',
        'batas_timur',
        'batas_selatan',
        'batas_barat',
        'batas_utara',
        'panjang_tanah',
        'lebar_tanah',
        'catatan',
        'alamat_wakif',
        
        // Kolom geospatial
        'koordinat',
        'latitude',
        'longitude'
    ];
    
    protected $casts = [
        'koordinat' => GeometryCast::class,
        'latitude' => 'double',
        'longitude' => 'double'
    ];
    
    // Changed to hasMany since one tanah can have many sertifikats
    public function sertifikats()
    {
        return $this->hasMany(Sertifikat::class, 'id_tanah', 'id_tanah');
    }

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'data_id', 'id_tanah');
    }

    public function pemetaanTanah(): HasMany
    {
        return $this->hasMany(PemetaanTanah::class, 'id_tanah', 'id_tanah');
    }

    /**
     * Scope untuk query berbasis lokasi
     */
    public function scopeNearby($query, $latitude, $longitude, $radius)
    {
        return $query->whereRaw(
            "ST_DWithin(
                koordinat::geography, 
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, 
                ?
            )",
            [$longitude, $latitude, $radius]
        );
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->latitude && $model->longitude) {
                $model->koordinat = [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $model->longitude,
                        (float) $model->latitude
                    ]
                ];
            }
        });
    }

    /**
     * Accessor untuk latitude
     */
    public function getLatitudeAttribute($value)
    {
        if ($value !== null) {
            return (float) $value;
        }

        $koordinat = $this->koordinat;
        return isset($koordinat['coordinates'][1]) ? (float) $koordinat['coordinates'][1] : null;
    }

    /**
     * Accessor untuk longitude
     */
    public function getLongitudeAttribute($value)
    {
        if ($value !== null) {
            return (float) $value;
        }

        $koordinat = $this->koordinat;
        return isset($koordinat['coordinates'][0]) ? (float) $koordinat['coordinates'][0] : null;
    }
}