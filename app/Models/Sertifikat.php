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
        'id_tanah',
        'noDokumenBastw',
        'noDokumenAIW',
        'noDokumenSW',
        'status',
        'legalitas',
        'user_id',
        'dokBastw',
        'dokAiw',
        'dokSw'
    ];

    // Relasi dengan Tanah
    public function tanah()
    {
        return $this->belongsTo(Tanah::class, 'id_tanah', 'id_tanah');
    }

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); // Add relationship with User model
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'data_id', 'id_sertifikat');
    }
}