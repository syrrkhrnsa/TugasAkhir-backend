<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'user_id'
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
}