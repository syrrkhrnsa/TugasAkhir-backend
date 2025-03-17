<?php

namespace App\Observers;

use App\Models\Sertifikat;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class SertifikatObserver
{
    public function created(Sertifikat $sertifikat)
    {
        $user = Auth::user();
        $userId = $this->getUserIdForLog($user, $sertifikat);

        ActivityLog::create([
            'user_id' => $userId,
            'action' => 'create',
            'model_type' => Sertifikat::class,
            'model_id' => $sertifikat->id_sertifikat,
            'changes' => json_encode($sertifikat->toArray())
        ]);
    }

    public function updated(Sertifikat $sertifikat)
    {
        $user = Auth::user();
        $userId = $this->getUserIdForLog($user, $sertifikat);

        $changes = $sertifikat->getChanges();
        ActivityLog::create([
            'user_id' => $userId,
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

    /**
     * Menentukan user_id untuk log activity berdasarkan role pengguna.
     *
     * @param \App\Models\User|null $user
     * @param \App\Models\Sertifikat $sertifikat
     * @return string
     */
    protected function getUserIdForLog($user, Sertifikat $sertifikat)
    {
        // ID role
        $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
        $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
        $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

        // Jika pengguna adalah Bidgar Wakaf, ambil user_id dari data JSON di approval
        if ($user && $user->role_id === $roleBidgarWakaf) {
            // Ambil approval terbaru yang terkait dengan sertifikat ini
            $approval = $sertifikat->approvals()->latest()->first();
            if ($approval) {
                $data = json_decode($approval->data, true);
                if (isset($data['user_id'])) {
                    return $data['user_id']; // Ambil user_id dari data JSON
                }
            }
        }

        // Jika pengguna adalah Pimpinan Jamaah, gunakan user_id dari data sertifikat
        if ($user && $user->role_id === $rolePimpinanJamaah) {
            return $sertifikat->user_id;
        }

        // Jika pengguna adalah Pimpinan Cabang, gunakan user_id yang sedang login
        if ($user && $user->role_id === $rolePimpinanCabang) {
            return $user->id;
        }

        // Default: gunakan user_id dari data sertifikat
        return $sertifikat->user_id;
    }
}