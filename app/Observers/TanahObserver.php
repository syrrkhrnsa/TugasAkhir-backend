<?php

namespace App\Observers;

use App\Models\Tanah;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class TanahObserver
{
    public function created(Tanah $tanah)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'model_type' => Tanah::class,
            'model_id' => $tanah->id_tanah,
            'changes' => json_encode($tanah->toArray())
        ]);
    }

    public function updated(Tanah $tanah)
    {
        $changes = $tanah->getChanges();
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update',
            'model_type' => Tanah::class,
            'model_id' => $tanah->id_tanah,
            'changes' => json_encode($changes)
        ]);
    }

    public function deleted(Tanah $tanah): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'model_type' => Tanah::class,
            'model_id' => $tanah->id_tanah,
            'changes' => json_encode($tanah->toArray())
        ]);
    }
}