<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    // Nama tabel yang digunakan oleh model
    protected $table = 'activity_logs';

    // Set primary key (otomatis menggunakan id jika tidak ada perubahan)
    protected $primaryKey = 'id';

    // Karena kita menggunakan UUID untuk user_id dan model_id, pastikan Eloquent tahu
    public $incrementing = false;
    
    // Kolom yang boleh diisi massal
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'changes'
    ];

    // Menangani kolom changes sebagai JSON
    protected $casts = [
        'changes' => 'array',
    ];

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}