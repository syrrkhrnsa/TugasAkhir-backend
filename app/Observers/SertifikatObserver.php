<?php

namespace App\Observers;

use App\Models\Sertifikat;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class SertifikatObserver
{
    public function created(Sertifikat $sertifikat)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'model_type' => Sertifikat::class,
            'model_id' => $sertifikat->id_sertifikat,
            'changes' => json_encode($sertifikat->toArray())
        ]);
    }

    public function updated(Sertifikat $sertifikat)
    {
        $changes = $sertifikat->getChanges();
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update',
            'model_type' => Sertifikat::class,
            'model_id' => $sertifikat->id_sertifikat,
            'changes' => json_encode($changes)
        ]);
    }

    public function deleted(Sertifikat $sertifikat)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'model_type' => Sertifikat::class,
            'model_id' => $sertifikat->id_sertifikat,
            'changes' => json_encode($sertifikat->toArray())
        ]);
    }
}