<?php

namespace App\Observers;

use App\Models\PemetaanTanah;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class PemetaanTanahObserver
{
    public function created(PemetaanTanah $pemetaanTanah)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'model_type' => PemetaanTanah::class,
            'model_id' => $pemetaanTanah->id_pemetaan_tanah,
            'changes' => json_encode($pemetaanTanah->toArray())
        ]);
    }

    public function updated(PemetaanTanah $pemetaanTanah)
    {
        $changes = $pemetaanTanah->getChanges();
        
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update',
            'model_type' => PemetaanTanah::class,
            'model_id' => $pemetaanTanah->id_pemetaan_tanah,
            'changes' => json_encode($changes)
        ]);
    }

    public function deleted(PemetaanTanah $pemetaanTanah)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'model_type' => PemetaanTanah::class,
            'model_id' => $pemetaanTanah->id_pemetaan_tanah,
            'changes' => json_encode($pemetaanTanah->toArray())
        ]);
    }
}