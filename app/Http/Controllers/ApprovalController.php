<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Tanah;
use App\Models\Sertifikat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use App\Models\User; // Import model User
use App\Notifications\ApprovalNotification; // Import notifikasi ApprovalNotification
use App\Notifications\RejectionNotification; // Import notifikasi RejectionNotification
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{

    public function show($id)
{
    $user = Auth::user();

    // Cek apakah role user adalah Bidgar Wakaf
    $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';
    if ($user->role_id !== $roleBidgarWakaf) {
        return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin untuk melihat detail persetujuan"], 403);
    }

    // Ambil data approval berdasarkan ID
    $approval = Approval::find($id);

    if (!$approval) {
        return response()->json(["status" => "error", "message" => "Approval tidak ditemukan"], 404);
    }

    // Decode data jika diperlukan
    $data = json_decode($approval->data, true);

    return response()->json([
        "status" => "success",
        "message" => "Data permintaan persetujuan ditemukan",
        "data" => [
            'approval' => $approval,
            'data' => $data
        ]
    ], 200);
}
    public function index()
    {
        $user = Auth::user();

    // Hanya Bidgar Wakaf yang bisa melihat notifikasi persetujuan
    $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';
    if ($user->role_id !== $roleBidgarWakaf) {
        return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin melihat permintaan persetujuan"], 403);
    }

    // Ambil semua data persetujuan yang masih dalam status "ditinjau"
    $pendingApprovals = Approval::where('status', 'ditinjau')->get();

    return response()->json([
        "status" => "success",
        "message" => "Data permintaan persetujuan berhasil diambil",
        "data" => $pendingApprovals
    ], 200);
    }


    public function approve($id)
{
    $user = Auth::user();
    if (!$user || $user->role_id !== '26b2b64e-9ae3-4e2e-9063-590b1bb00480') {
        return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin"], 403);
    }

    $approval = Approval::find($id);
    if (!$approval) {
        return response()->json(["status" => "error", "message" => "Permintaan tidak ditemukan"], 404);
    }

    // Decode JSON dari 'data' yang ada dalam approval
    $data = json_decode($approval->data, true);

    // Pastikan 'data' sudah terdecode dengan benar
    if (!$data) {
        return response()->json(["status" => "error", "message" => "Data tidak valid"], 400);
    }

    // Update status approval
    $approval->update(['status' => 'disetujui', 'approver_id' => $user->id]);

    // Cek tipe approval
    if ($approval->type === 'tanah') {
        Tanah::updateOrCreate(
            ['id_tanah' => $data['id_tanah']],
            array_merge($data, ['status' => 'disetujui', 'user_id' => $approval->user_id])
        );
    } elseif ($approval->type === 'sertifikat') {
        // Jika tipe approval adalah sertifikat, update data yang sudah ada
        Sertifikat::where('id_sertifikat', $data['id_sertifikat'])
            ->update(['status' => 'disetujui']);
    } else {
        return response()->json(["status" => "error", "message" => "Tipe approval tidak valid"], 400);
    }

    $pimpinanJamaah = User::find($approval->user_id);
    $pimpinanJamaah->notify(new ApprovalNotification($approval, 'approve', 'pimpinan_jamaah'));

    return response()->json(["status" => "success", "message" => "Permintaan disetujui"], 200);   
}

    public function approveUpdate($id)
    {
        $user = Auth::user();
        if (!$user || $user->role_id !== '26b2b64e-9ae3-4e2e-9063-590b1bb00480') {
            return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin"], 403);
        }

        $approval = Approval::find($id);
        if (!$approval) {
            return response()->json(["status" => "error", "message" => "Permintaan tidak ditemukan"], 404);
        }

        // Decode data untuk melihat perubahan yang diajukan
        $data = json_decode($approval->data, true);

        // Validasi data
        if (!$data || !isset($data['previous_data'], $data['updated_data'])) {
            Log::error('Data approval tidak valid', ['approval_id' => $id]);
            return response()->json(["status" => "error", "message" => "Data approval tidak valid"], 400);
        }

        // Update status approval terlebih dahulu
        $approval->update([
            'status' => 'disetujui',
            'approver_id' => $user->id,
            'updated_at' => now()
        ]);

        try {
            if ($approval->type === 'tanah_update') {
                $tanah = Tanah::find($data['previous_data']['id_tanah']);
                if (!$tanah) {
                    Log::error('Data tanah tidak ditemukan', ['id_tanah' => $data['previous_data']['id_tanah']]);
                    return response()->json(["status" => "error", "message" => "Data tanah tidak ditemukan"], 404);
                }

                $tanah->update(array_merge(
                    $data['updated_data'],
                    ['status' => 'disetujui']
                ));

            } elseif ($approval->type === 'sertifikat_update') {
                // Gunakan id_sertifikat dari previous_data jika tidak ada di updated_data
                $sertifikatId = $data['updated_data']['id_sertifikat'] ?? $data['previous_data']['id_sertifikat'];

                $sertifikat = Sertifikat::find($sertifikatId);
                if (!$sertifikat) {
                    Log::error('Data sertifikat tidak ditemukan', ['id_sertifikat' => $sertifikatId]);
                    return response()->json(["status" => "error", "message" => "Data sertifikat tidak ditemukan"], 404);
                }

                $sertifikat->update(array_merge(
                    $data['updated_data'],
                    ['status' => 'disetujui']
                ));

            } else {
                return response()->json(["status" => "error", "message" => "Tipe approval tidak valid"], 400);
            }

            // Kirim notifikasi
            $pimpinanJamaah = User::find($approval->user_id);
            $pimpinanJamaah->notify(new ApprovalNotification($approval, 'approve_update', 'pimpinan_jamaah'));

            return response()->json(["status" => "success", "message" => "Permintaan pembaruan disetujui"], 200);

        } catch (\Exception $e) {
            Log::error('Gagal memproses approval: ' . $e->getMessage(), [
                'approval_id' => $id,
                'exception' => $e
            ]);
            return response()->json(["status" => "error", "message" => "Terjadi kesalahan saat memproses approval"], 500);
        }
    }

    public function rejectUpdate($id)
    {
        $user = Auth::user();
        if (!$user || $user->role_id !== '26b2b64e-9ae3-4e2e-9063-590b1bb00480') {
            return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin"], 403);
        }

        $approval = Approval::find($id);
        if (!$approval) {
            return response()->json(["status" => "error", "message" => "Permintaan tidak ditemukan"], 404);
        }

        // Decode data JSON
        $data = json_decode($approval->data, true);

        if (!$data || !isset($data['previous_data'])) {
            return response()->json(["status" => "error", "message" => "Data tidak valid"], 400);
        }

        // Cek tipe approval
        if ($approval->type === 'tanah_update') {
            // Jika tipe approval adalah tanah_update, kembalikan data tanah ke versi sebelumnya
            $tanah = Tanah::where('id_tanah', $data['previous_data']['id_tanah'])->first();
            $tanah->update(['status' => 'disetujui']);
            if (!$tanah) {
                return response()->json(["status" => "error", "message" => "Data tanah tidak ditemukan"], 404);
            }
            $tanah->update($data['previous_data']);
        } elseif ($approval->type === 'sertifikat_update') {
            // Jika tipe approval adalah sertifikat_update, kembalikan data sertifikat ke versi sebelumnya
            $sertifikat = Sertifikat::where('id_sertifikat', $data['previous_data']['id_sertifikat'])->first();
            $sertifikat->update(['status' => 'disetujui']);
            if (!$sertifikat) {
                return response()->json(["status" => "error", "message" => "Data sertifikat tidak ditemukan"], 404);
            }
            $sertifikat->update($data['previous_data']);
        } else {
            return response()->json(["status" => "error", "message" => "Tipe approval tidak valid"], 400);
        }

        // Update status persetujuan menjadi 'ditolak'
        $approval->update(['status' => 'ditolak', 'approver_id' => $user->id]);

        // Kirim notifikasi ke Pimpinan Jamaah
        $pimpinanJamaah = User::find($approval->user_id);
        $pimpinanJamaah->notify(new RejectionNotification($approval, 'reject_update', 'pimpinan_jamaah'));

        return response()->json(["status" => "success", "message" => "Permintaan pembaruan ditolak"], 200);
    }


    public function reject($id)
{
    $user = Auth::user();
    if (!$user || $user->role_id !== '26b2b64e-9ae3-4e2e-9063-590b1bb00480') {
        return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin"], 403);
    }

    $approval = Approval::find($id);
    if (!$approval) {
        return response()->json(["status" => "error", "message" => "Permintaan tidak ditemukan"], 404);
    }

    // Decode JSON dari 'data' yang ada dalam approval
    $data = json_decode($approval->data, true);

    // Pastikan 'data' sudah terdecode dengan benar
    if (!$data) {
        return response()->json(["status" => "error", "message" => "Data tidak valid"], 400);
    }

    // Cek tipe approval
    if ($approval->type === 'tanah') {
        // Jika tipe approval adalah tanah, simpan data ke tabel Tanah dengan status 'ditolak'
        Tanah::create(array_merge($data, ['status' => 'ditolak', 'user_id' => $approval->user_id]));
    } elseif ($approval->type === 'sertifikat') {
        // Jika tipe approval adalah sertifikat, simpan data ke tabel Sertifikat dengan status 'ditolak'
        Sertifikat::create(array_merge($data, ['status' => 'ditolak', 'user_id' => $approval->user_id]));
    } else {
        return response()->json(["status" => "error", "message" => "Tipe approval tidak valid"], 400);
    }

    // Update status persetujuan menjadi 'ditolak'
    $approval->update(['status' => 'ditolak', 'approver_id' => $user->id]);

    // Kirim notifikasi ke Pimpinan Jamaah
    $pimpinanJamaah = User::find($approval->user_id);
    $pimpinanJamaah->notify(new RejectionNotification($approval, 'reject', 'pimpinan_jamaah'));

    return response()->json(["status" => "success", "message" => "Permintaan ditolak dan data disimpan dengan status ditolak"], 200);
}

    public function getByType($type)
    {
        $user = Auth::user();

        // Validasi tipe yang diperbolehkan
        $allowedTypes = ['tanah', 'tanah_update', 'fasilitas', 'fasilitas_update', 'inventaris', 'inventaris_update'];
        if (!in_array($type, $allowedTypes)) {
            return response()->json(["status" => "error", "message" => "Tipe tidak valid"], 400);
        }

        // Ambil data persetujuan berdasarkan tipe
        $approvals = Approval::where('type', $type)
            ->where('status', 'ditinjau')
            ->get()
            ->map(function ($approval) {
                $parsedData = json_decode($approval->data, true);

                return array_merge($parsedData, [
                    'status' => $approval->status
                ]);

            });

        return response()->json([
            "status" => "success",
            "message" => "Data permintaan persetujuan berhasil diambil",
            "data" => $approvals
        ], 200);
    }

}