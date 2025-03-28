<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Tanah;
use App\Models\Sertifikat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Notifications\ApprovalNotification;
use App\Notifications\RejectionNotification;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    // Role constants
    const ROLE_BIDGAR_WAKAF = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';
    
    public function show($id)
    {
        $user = Auth::user();

        if ($user->role_id !== self::ROLE_BIDGAR_WAKAF) {
            return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin untuk melihat detail persetujuan"], 403);
        }

        $approval = Approval::find($id);
        if (!$approval) {
            return response()->json(["status" => "error", "message" => "Approval tidak ditemukan"], 404);
        }

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

        if ($user->role_id !== self::ROLE_BIDGAR_WAKAF) {
            return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin melihat permintaan persetujuan"], 403);
        }

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
        if (!$user || $user->role_id !== self::ROLE_BIDGAR_WAKAF) {
            return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin"], 403);
        }

        $approval = Approval::find($id);
        if (!$approval) {
            return response()->json(["status" => "error", "message" => "Permintaan tidak ditemukan"], 404);
        }

        $data = json_decode($approval->data, true);
        if (!$data) {
            return response()->json(["status" => "error", "message" => "Data tidak valid"], 400);
        }

        DB::beginTransaction();
        try {
            switch ($approval->type) {
                case 'tanah':
                    $tanah = Tanah::create(array_merge($data, [
                        'status' => 'disetujui',
                        'user_id' => $approval->user_id,
                    ]));
                    break;

                case 'sertifikat':
                    $sertifikat = Sertifikat::create(array_merge($data, [
                        'status' => 'disetujui',
                        'user_id' => $approval->user_id,
                    ]));
                    break;

                default:
                    DB::rollBack();
                    return response()->json(["status" => "error", "message" => "Tipe approval tidak valid"], 400);
            }

            $approval->update(['status' => 'disetujui', 'approver_id' => $user->id]);
            DB::commit();

            $pimpinanJamaah = User::find($approval->user_id);
            $pimpinanJamaah->notify(new ApprovalNotification($approval, 'approve', 'pimpinan_jamaah'));

            $responseData = ['approval' => $approval];
            if (isset($tanah)) $responseData['tanah'] = $tanah;
            if (isset($sertifikat)) $responseData['sertifikat'] = $sertifikat;

            return response()->json([
                "status" => "success",
                "message" => "Permintaan disetujui.",
                "data" => $responseData
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat menyimpan data",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approveUpdate($id)
    {
        $user = Auth::user();
        if (!$user || $user->role_id !== self::ROLE_BIDGAR_WAKAF) {
            return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin"], 403);
        }

        $approval = Approval::find($id);
        if (!$approval) {
            return response()->json(["status" => "error", "message" => "Permintaan tidak ditemukan"], 404);
        }

        $data = json_decode($approval->data, true);
        if (!$data || !isset($data['previous_data'], $data['updated_data'])) {
            Log::error('Data approval tidak valid', ['approval_id' => $id]);
            return response()->json(["status" => "error", "message" => "Data approval tidak valid"], 400);
        }

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
        if (!$user || $user->role_id !== self::ROLE_BIDGAR_WAKAF) {
            return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin"], 403);
        }

        $approval = Approval::find($id);
        if (!$approval) {
            return response()->json(["status" => "error", "message" => "Permintaan tidak ditemukan"], 404);
        }

        $data = json_decode($approval->data, true);
        if (!$data || !isset($data['previous_data'])) {
            return response()->json(["status" => "error", "message" => "Data tidak valid"], 400);
        }

        if ($approval->type === 'tanah_update') {
            $tanah = Tanah::where('id_tanah', $data['previous_data']['id_tanah'])->first();
            if (!$tanah) {
                return response()->json(["status" => "error", "message" => "Data tanah tidak ditemukan"], 404);
            }
            $tanah->update(array_merge($data['previous_data'], ['status' => 'disetujui']));
        } elseif ($approval->type === 'sertifikat_update') {
            $sertifikat = Sertifikat::where('id_sertifikat', $data['previous_data']['id_sertifikat'])->first();
            if (!$sertifikat) {
                return response()->json(["status" => "error", "message" => "Data sertifikat tidak ditemukan"], 404);
            }
            $sertifikat->update(array_merge($data['previous_data'], ['status' => 'disetujui']));
        } else {
            return response()->json(["status" => "error", "message" => "Tipe approval tidak valid"], 400);
        }

        $approval->update(['status' => 'ditolak', 'approver_id' => $user->id]);

        $pimpinanJamaah = User::find($approval->user_id);
        $pimpinanJamaah->notify(new RejectionNotification($approval, 'reject_update', 'pimpinan_jamaah'));

        return response()->json(["status" => "success", "message" => "Permintaan pembaruan ditolak"], 200);
    }

    public function reject($id)
    {
        $user = Auth::user();
        if (!$user || $user->role_id !== self::ROLE_BIDGAR_WAKAF) {
            return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin"], 403);
        }

        $approval = Approval::find($id);
        if (!$approval) {
            return response()->json(["status" => "error", "message" => "Permintaan tidak ditemukan"], 404);
        }

        $data = json_decode($approval->data, true);
        if (!$data) {
            return response()->json(["status" => "error", "message" => "Data tidak valid"], 400);
        }

        if ($approval->type === 'tanah') {
            Tanah::create(array_merge($data, [
                'status' => 'ditolak', 
                'user_id' => $approval->user_id
            ]));
        } elseif ($approval->type === 'sertifikat') {
            Sertifikat::create(array_merge($data, [
                'status' => 'ditolak', 
                'user_id' => $approval->user_id
            ]));
        } else {
            return response()->json(["status" => "error", "message" => "Tipe approval tidak valid"], 400);
        }

        $approval->update(['status' => 'ditolak', 'approver_id' => $user->id]);

        $pimpinanJamaah = User::find($approval->user_id);
        $pimpinanJamaah->notify(new RejectionNotification($approval, 'reject', 'pimpinan_jamaah'));

        return response()->json(["status" => "success", "message" => "Permintaan ditolak dan data disimpan dengan status ditolak"], 200);
    }

    public function getByType($type)
    {
        $user = Auth::user();

        $allowedTypes = [
            'tanah', 'tanah_update', 
            'sertifikat', 'sertifikat_update'
        ];
        
        if (!in_array($type, $allowedTypes)) {
            return response()->json(["status" => "error", "message" => "Tipe tidak valid"], 400);
        }

        $approvals = Approval::where('type', $type)
            ->where('status', 'ditinjau')
            ->get()
            ->map(function ($approval) {
                $parsedData = json_decode($approval->data, true);
                return array_merge($parsedData, ['status' => $approval->status]);
            });

        return response()->json([
            "status" => "success",
            "message" => "Data permintaan persetujuan berhasil diambil",
            "data" => $approvals
        ], 200);
    }
}