<?php

namespace App\Observers;

use App\Models\Inventaris;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class InventarisObserver
{
    public function created(Inventaris $inventaris)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'model_type' => Inventaris::class,
            'model_id' => $inventaris->id_inventaris,
            'changes' => json_encode($inventaris->toArray()),
        ]);
    }

    public function updated(Inventaris $inventaris)
    {
        $changes = $inventaris->getChanges();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'update',
            'model_type' => Inventaris::class,
            'model_id' => $inventaris->id_inventaris,
            'changes' => json_encode($changes),
        ]);
    }

    public function deleted(Inventaris $inventaris)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'model_type' => Inventaris::class,
            'model_id' => $inventaris->id_inventaris,
            'changes' => json_encode($inventaris->toArray()),
        ]);
    }
}