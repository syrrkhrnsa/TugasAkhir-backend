<?php

namespace App\Observers;

use App\Models\PemetaanFasilitas;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class PemetaanFasilitasObserver
{
    public function created(PemetaanFasilitas $pemetaanFasilitas)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'model_type' => PemetaanFasilitas::class,
            'model_id' => $pemetaanFasilitas->id_pemetaan_fasilitas,
            'changes' => json_encode($pemetaanFasilitas->toArray())
        ]);
    }

    public function updated(PemetaanFasilitas $pemetaanFasilitas)
    {
        $changes = $pemetaanFasilitas->getChanges();
        
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update',
            'model_type' => PemetaanFasilitas::class,
            'model_id' => $pemetaanFasilitas->id_pemetaan_fasilitas,
            'changes' => json_encode($changes)
        ]);
    }

    public function deleted(PemetaanFasilitas $pemetaanFasilitas)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'model_type' => PemetaanFasilitas::class,
            'model_id' => $pemetaanFasilitas->id_pemetaan_fasilitas,
            'changes' => json_encode($pemetaanFasilitas->toArray())
        ]);
    }
}