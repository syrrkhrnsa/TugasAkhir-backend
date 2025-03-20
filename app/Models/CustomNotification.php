<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class CustomNotification extends DatabaseNotification
{
    // Tambahkan relasi ke model Approval
    public function approval()
    {
        return $this->belongsTo(Approval::class, 'approval_id');
    }
}