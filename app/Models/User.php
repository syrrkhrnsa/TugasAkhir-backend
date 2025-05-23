<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

     public $incrementing = false;
     protected $primaryKey = 'id';
     protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'username',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Relasi dengan ActivityLog
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
    public function tanah()
    {
        return $this->hasMany(Tanah::class);
    }
    public function sertifikat()
    {
        return $this->hasMany(Sertifikat::class);
    }
    public function notifications()
    {
        return $this->morphMany(CustomNotification::class, 'notifiable')->latest();
    }

    public function pemetaanTanah()
    {
        return $this->hasMany(PemetaanTanah::class, 'id_user');
    }

    public function pemetaanFasilitas()
    {
        return $this->hasMany(PemetaanFasilitas::class, 'id_user');
    }
}