<?php

namespace App\Http\Controllers;

use App\Models\Sertifikat;
use App\Models\Approval;
use App\Notifications\ApprovalNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SertifikatWakafController extends Controller
{
    // Menampilkan data sertifikat untuk publik (hanya yang statusnya "disetujui")
    public function publicIndex()
    {
        try {
            $sertifikats = Sertifikat::where('status', 'disetujui')->get();

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil diambil",
                "data" => $sertifikats
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat mengambil data sertifikat untuk publik', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat mengambil data",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showLegalitas($id)
    {
        try {
            // Cari sertifikat berdasarkan ID
            $sertifikat = Sertifikat::find($id);

            // Jika data tidak ditemukan
            if (!$sertifikat) {
                return response()->json([
                    "status" => "error",
                    "message" => "Data sertifikat tidak ditemukan"
                ], Response::HTTP_NOT_FOUND);
            }

            // Ambil data legalitas
            $legalitas = $sertifikat->legalitas;

            return response()->json([
                "status" => "success",
                "message" => "Data legalitas berhasil diambil",
                "data" => [
                    "id_sertifikat" => $sertifikat->id_sertifikat,
                    "legalitas" => $legalitas
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat mengambil data legalitas",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Menampilkan data sertifikat berdasarkan role pengguna
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Log::error('User tidak terautentikasi');
                return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], Response::HTTP_UNAUTHORIZED);
            }

            // ID role yang digunakan untuk filtering
            $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
            $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

            // Query data berdasarkan role pengguna
            if ($user->role_id === $rolePimpinanJamaah) {
                // Pimpinan Jamaah: hanya melihat data berdasarkan user_id
                $sertifikats = Sertifikat::where('user_id', $user->id)->get();
            } elseif ($user->role_id === $rolePimpinanCabang || $user->role_id === $roleBidgarWakaf) {
                // Pimpinan Cabang dan Bidgar Wakaf: hanya melihat data dengan status "disetujui"
                $sertifikats = Sertifikat::where('status', 'disetujui')->get();
            } else {
                Log::error('Akses ditolak untuk user', ['user_id' => $user->id, 'role_id' => $user->role_id]);
                return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin untuk melihat data"], Response::HTTP_FORBIDDEN);
            }

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil diambil",
                "data" => $sertifikats
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat mengambil data sertifikat', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat mengambil data",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Menyimpan data sertifikat baru
    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'noDokumen' => 'nullable|string|unique:sertifikats',
            'dokBastw' => 'nullable|string',
            'dokAiw' => 'nullable|string',
            'dokSw' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi gagal",
                "errors" => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], Response::HTTP_UNAUTHORIZED);
        }

        // ID role
        $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
        $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
        $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

        if ($user->role_id === $rolePimpinanJamaah) {
            // Jika Pimpinan Jamaah, data disimpan ke tabel Approval
            $data = [
                'id_sertifikat' => Str::uuid(),
                'id_tanah' => $request->id_tanah,
                'noDokumen' => $request->noDokumen,
                'legalitas' => 'N/A',
                'dokBastw' => $request->dokBastw,
                'dokAiw' => $request->dokAiw,
                'dokSw' => $request->dokSw,
            ];

            $approval = Approval::create([
                'user_id' => $user->id,
                'type' => 'sertifikat',
                'data_id' => Str::uuid(),
                'data' => json_encode($data),
                'status' => 'ditinjau',
            ]);

            // Kirim notifikasi ke Bidgar Wakaf
            $bidgarWakaf = User::where('role_id', $roleBidgarWakaf)->get();
            foreach ($bidgarWakaf as $bidgar) {
                $bidgar->notify(new ApprovalNotification($approval, 'create', 'bidgar')); // Tambahkan 'bidgar' sebagai recipient
            }

            return response()->json([
                "status" => "success",
                "message" => "Permintaan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
            ], Response::HTTP_CREATED);
        } else {
            // Jika Pimpinan Cabang atau Bidgar Wakaf, langsung simpan ke tabel Sertifikat
            $sertifikat = Sertifikat::create([
                'id_sertifikat' => Str::uuid(),
                'id_tanah' => $request->id_tanah,
                'noDokumen' => $request->noDokumen,
                'status' => 'disetujui',
                'legalitas' => 'N/A',
                'user_id' => $user->id,
                'dokBastw' => $request->dokBastw,
                'dokAiw' => $request->dokAiw,
                'dokSw' => $request->dokSw,
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil ditambahkan dan disetujui.",
                "data" => $sertifikat
            ], Response::HTTP_CREATED);
        }
    } catch (\Exception $e) {
        return response()->json([
            "status" => "error",
            "message" => "Terjadi kesalahan saat menyimpan data",
            "error" => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    // Memperbarui data sertifikat
    public function update(Request $request, $id)
{
    try {
        $validator = Validator::make($request->all(), [
            'noDokumen' => 'sometimes|string|unique:sertifikats,noDokumen,' . $id . ',id_sertifikat',
            'dokBastw' => 'nullable|string',
            'dokAiw' => 'nullable|string',
            'dokSw' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi gagal",
                "errors" => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], Response::HTTP_UNAUTHORIZED);
        }

        $sertifikat = Sertifikat::find($id);
        if (!$sertifikat) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }

        // Simpan data sebelumnya
        $previousData = $sertifikat->toArray();

        // ID role
        $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
        $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
        $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

        if ($user->role_id === $rolePimpinanJamaah) {
            // Jika Pimpinan Jamaah, update disimpan sebagai Approval
            $data = [
                'id_sertifikat' => $sertifikat->id_sertifikat, // Sertakan id_sertifikat
                'noDokumen' => $request->noDokumen ?? $sertifikat->noDokumen,
                'dokBastw' => $request->dokBastw ?? $sertifikat->dokBastw,
                'dokAiw' => $request->dokAiw ?? $sertifikat->dokAiw,
                'dokSw' => $request->dokSw ?? $sertifikat->dokSw,
                'id_tanah' => $sertifikat->id_tanah, // Sertakan id_tanah jika diperlukan
            ];

            // Buat approval dengan data sebelumnya dan data yang diperbarui
            $approval = Approval::create([
                'user_id' => $user->id,
                'type' => 'sertifikat_update',
                'data_id' => $sertifikat->id_sertifikat,
                'data' => json_encode([
                    'previous_data' => $previousData,
                    'updated_data' => $data
                ]),
                'status' => 'ditinjau',
            ]);

            // Kirim notifikasi ke Bidgar Wakaf
            $bidgarWakaf = User::where('role_id', $roleBidgarWakaf)->get();
            foreach ($bidgarWakaf as $bidgar) {
                $bidgar->notify(new ApprovalNotification($approval, 'update', 'bidgar')); // Tambahkan 'bidgar' sebagai recipient
            }

            return response()->json([
                "status" => "success",
                "message" => "Permintaan pembaruan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
            ], Response::HTTP_CREATED);
        } else {
            // Jika Pimpinan Cabang atau Bidgar Wakaf, langsung update data
            $sertifikat->update($request->all());

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil diperbarui.",
                "data" => $sertifikat
            ], Response::HTTP_OK);
        }
    } catch (\Exception $e) {
        return response()->json([
            "status" => "error",
            "message" => "Terjadi kesalahan saat memperbarui data",
            "error" => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    public function updateLegalitas(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'legalitas' => 'required|string', // Hanya validasi untuk legalitas
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Validasi gagal",
                    "errors" => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], Response::HTTP_UNAUTHORIZED);
            }

            $sertifikat = Sertifikat::find($id);
            if (!$sertifikat) {
                return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
            }

            // Update hanya field legalitas
            $sertifikat->legalitas = $request->legalitas;
            $sertifikat->save();

            return response()->json([
                "status" => "success",
                "message" => "Data legalitas berhasil diperbarui.",
                "data" => $sertifikat
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat memperbarui data legalitas",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Menghapus data sertifikat
    public function destroy($id)
    {
        $sertifikat = Sertifikat::find($id);
        if (!$sertifikat) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }

        $sertifikat->delete();
        return response()->json(["status" => "success", "message" => "Data berhasil dihapus"], Response::HTTP_OK);
    }
}