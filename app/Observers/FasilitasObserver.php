<?php

namespace App\Observers;

use App\Models\Fasilitas;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class FasilitasObserver
{
    public function created(Fasilitas $fasilitas)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'model_type' => Fasilitas::class,
            'model_id' => $fasilitas->id_fasilitas,
            'changes' => json_encode($fasilitas->toArray()),
        ]);
    }

    public function updated(Fasilitas $fasilitas)
    {
        $changes = $fasilitas->getChanges();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update',
            'model_type' => Fasilitas::class,
            'model_id' => $fasilitas->id_fasilitas,
            'changes' => json_encode($changes),
        ]);
    }

    public function deleted(Fasilitas $fasilitas)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'model_type' => Fasilitas::class,
            'model_id' => $fasilitas->id_fasilitas,
            'changes' => json_encode($fasilitas->toArray()),
        ]);
    }
}