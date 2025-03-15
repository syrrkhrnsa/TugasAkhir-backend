<?php

namespace App\Observers;

use App\Models\Tanah;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;


class TanahObserver
{
    public function created(Tanah $tanah)
    {
        $user = Auth::user();
        $userId = $this->getUserIdForLog($user, $tanah);

        ActivityLog::create([
            'user_id' => $userId,
            'action' => 'create',
            'model_type' => Tanah::class,
            'model_id' => $tanah->id_tanah,
            'changes' => json_encode($tanah->toArray())
        ]);
    }

    public function updated(Tanah $tanah)
    {
        $user = Auth::user();
        $userId = $this->getUserIdForLog($user, $tanah);

        $changes = $tanah->getChanges();
        ActivityLog::create([
            'user_id' => $userId,
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

    /**
     * Menentukan user_id untuk log activity berdasarkan role pengguna.
     *
     * @param \App\Models\User|null $user
     * @param \App\Models\Tanah $tanah
     * @return string
     */
    protected function getUserIdForLog($user, Tanah $tanah)
{
    // ID role
    $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
    $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
    $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

    // Jika pengguna adalah Bidgar Wakaf, ambil user_id dari data JSON di approval
    if ($user && $user->role_id === $roleBidgarWakaf) {
        // Ambil approval terbaru yang terkait dengan tanah ini
        $approval = $tanah->approvals()->latest()->first();
        if ($approval) {
            $data = json_decode($approval->data, true);
            if (isset($data['user_id'])) {
                return $data['user_id']; // Ambil user_id dari data JSON
            }
        }
    }

    // Jika pengguna adalah Pimpinan Jamaah, gunakan user_id dari data tanah
    if ($user && $user->role_id === $rolePimpinanJamaah) {
        return $tanah->user_id;
    }

    // Jika pengguna adalah Pimpinan Cabang, gunakan user_id yang sedang login
    if ($user && $user->role_id === $rolePimpinanCabang) {
        return $user->id;
    }

    // Default: gunakan user_id dari data tanah
    return $tanah->user_id;
}
}