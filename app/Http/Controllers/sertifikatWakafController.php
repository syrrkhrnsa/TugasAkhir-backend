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
use Illuminate\Support\Facades\Storage;


class SertifikatWakafController extends Controller
{
    // Menampilkan data sertifikat untuk publik (hanya yang statusnya "disetujui")
    public function publicIndex()
    {

            $sertifikats = Sertifikat::where('status', 'disetujui')->get();

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil diambil",
                "data" => $sertifikats
            ], Response::HTTP_OK);
    }

    public function getSertifikatByIdTanah($id_tanah)
    {

        try {
            if (app()->environment('testing') && request()->has('force_db_error')) {
                throw new \Exception('Database error for testing');
            }

            // Cari sertifikat berdasarkan id_tanah
            $sertifikat = Sertifikat::where('id_tanah', $id_tanah)->get(); // Ubah ke get()

            // Jika data tidak ditemukan
            if ($sertifikat->isEmpty()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Data sertifikat untuk tanah ini tidak ditemukan"
                ], Response::HTTP_NOT_FOUND);
            }

            // Jika data ditemukan, kembalikan respons JSON
            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil diambil",
                "data" => $sertifikat // Ini akan mengembalikan array
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Log error jika terjadi kesalahan
            Log::error('Terjadi kesalahan saat mengambil data sertifikat', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat mengambil data sertifikat",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showLegalitas($id_tanah)
    {

        try {
            if (app()->environment('testing') && request()->has('force_db_error')) {
                throw new \Exception('Database error for testing');
            }

            // Cari sertifikat berdasarkan id_tanah
            $sertifikat = Sertifikat::where('id_tanah', $id_tanah)->first();

            // Jika data tidak ditemukan
            if (!$sertifikat) {
                return response()->json([
                    "status" => "error",
                    "message" => "Data sertifikat untuk tanah ini tidak ditemukan"
                ], Response::HTTP_NOT_FOUND);
            }

            // Ambil data legalitas
            $legalitas = $sertifikat->jenis_sertifikat;

            return response()->json([
                "status" => "success",
                "message" => "Data legalitas berhasil diambil",
                "data" => [
                    "id_tanah" => $sertifikat->id_tanah,
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
            if (app()->environment('testing') && request()->has('force_db_error')) {
                throw new \Exception('Database error for testing');
            }
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

    public function show($id)
    {
        // Ambil data sertifikat berdasarkan ID
        $sertifikat = Sertifikat::find($id);

        if (!$sertifikat) {
            return response()->json(['message' => 'Sertifikat tidak ditemukan'], 404);
        }

        // Kembalikan data sertifikat sebagai response JSON
        return response()->json($sertifikat);
    }

    // Menyimpan data sertifikat baru
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'no_dokumen' => 'nullable|string|unique:sertifikats',
                'dokumen' => 'nullable|string',
                'jenis_sertifikat' => 'nullable|string',
                'status_pengajuan' => 'nullable|string',
                'id_tanah' => 'required|uuid',
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

                $data = [
                    'id_sertifikat' => Str::uuid(),
                    'id_tanah' => $request->id_tanah,
                    'no_dokumen' => $request->no_dokumen,
                    'dokumen' => $request->dokumen,
                    'jenis_sertifikat' => $request->jenis_sertifikat,
                    'status_pengajuan' => $request->status_pengajuan,
                    'status' => 'ditinjau',
                    'user_id' => $user->id,
                ];


                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'sertifikat',
                    'data_id' => $data['id_sertifikat'],
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
                    'no_dokumen' => $request->no_dokumen,
                    'dokumen' => $request->dokumen,
                    'jenis_sertifikat' => $request->jenis_sertifikat,
                    'status_pengajuan' => $request->status_pengajuan,
                    'status' => "disetujui",
                    'user_id' => $user->id,
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

    public function update(Request $request, $id)
    {
        try {

            $user = Auth::user();
            if (!$user) {
                return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], 401);
            }

            // ID role
            $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

            // Validasi role
            if (!in_array($user->role_id, [$rolePimpinanJamaah, $roleBidgarWakaf])) {
                return response()->json([
                    "status" => "error",
                    "message" => "Anda tidak memiliki izin untuk melakukan pembaruan"
                ], 403);
            }

            // Cek data sertifikat
            $sertifikat = Sertifikat::findOrFail($id);

            // Validasi request
            $validator = Validator::make($request->all(), [
                'noDokumenBastw' => 'nullable|string|unique:sertifikats,noDokumenBastw,' . $id . ',id_sertifikat',
                'noDokumenAIW'   => 'nullable|string|unique:sertifikats,noDokumenAIW,' . $id . ',id_sertifikat',
                'noDokumenSW'    => 'nullable|string|unique:sertifikats,noDokumenSW,' . $id . ',id_sertifikat',
                'dokBastw'       => 'nullable|file|mimes:pdf|max:2048',
                'dokAiw'         => 'nullable|file|mimes:pdf|max:2048',
                'dokSw'          => 'nullable|file|mimes:pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Validasi gagal",
                    "errors" => $validator->errors()
                ], 400);
            }

            $updateData = [];
            $fileChanges = [];

            // Handle text inputs
            $textFields = ['noDokumenBastw', 'noDokumenAIW', 'noDokumenSW'];
            foreach ($textFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            // Handle file uploads
            $fileFields = ['dokBastw', 'dokAiw', 'dokSw'];
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    // Hapus file lama jika ada
                    if ($sertifikat->$field) {
                        Storage::disk('public')->delete($sertifikat->$field);
                    }

                    // Simpan file baru
                    $path = $request->file($field)->store('dokumen', 'public');
                    $updateData[$field] = $path;
                    $fileChanges[$field] = $path;
                }
            }

            // Jika tidak ada data yang diupdate
            if (empty($updateData)) {
                return response()->json([
                    "status" => "error",
                    "message" => "Tidak ada data yang diperbarui"
                ], 400);
            }

            // Jika user Pimpinan Jamaah, buat approval
            if ($user->role_id === $rolePimpinanJamaah) {
                $previousData = $sertifikat->toArray();

                // Simpan perubahan sementara
                $sertifikat->fill($updateData);
                $sertifikat->status = 'ditinjau';
                $sertifikat->save();

                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'sertifikat_update',
                    'data_id' => $sertifikat->id_sertifikat,
                    'status' => 'ditinjau',
                    'data' => json_encode([
                        'previous_data' => $previousData,
                        'updated_data' => $updateData,
                        'file_changes' => $fileChanges,
                        'id_sertifikat' => $sertifikat->id_sertifikat
                    ]),
                ]);

                // Kirim notifikasi ke Bidgar Wakaf
                $bidgarUsers = User::where('role_id', $roleBidgarWakaf)->get();
                foreach ($bidgarUsers as $bidgar) {
                    $bidgar->notify(new ApprovalNotification($approval, 'update', 'bidgar'));
                }

                return response()->json([
                    "status" => "success",
                    "message" => "Permintaan pembaruan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
                    "approval_id" => $approval->id
                ], 201);
            }

            // Jika user Bidgar Wakaf, langsung update
            $sertifikat->update($updateData);

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil diperbarui.",
                "data" => $sertifikat,
                "file_paths" => $fileChanges
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in update sertifikat: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat memperbarui data",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function updateJenisSertifikat(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'jenis_sertifikat' => 'nullable|string',
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

            // Update hanya field jenis_sertifikat
            $sertifikat->jenis_sertifikat = $request->jenis_sertifikat;
            $sertifikat->save();

            return response()->json([
                "status" => "success",
                "message" => "Jenis sertifikat berhasil diperbarui.",
                "data" => $sertifikat
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat memperbarui jenis sertifikat",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatusPengajuan(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status_pengajuan' => 'nullable|string',
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

            // ID role untuk validasi
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';
            
            // Hanya Bidgar Wakaf yang bisa update status pengajuan
            if ($user->role_id !== $roleBidgarWakaf) {
                return response()->json([
                    "status" => "error",
                    "message" => "Anda tidak memiliki izin untuk mengubah status pengajuan"
                ], Response::HTTP_FORBIDDEN);
            }

            $sertifikat = Sertifikat::find($id);
            if (!$sertifikat) {
                return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
            }

            // Update hanya field status_pengajuan
            $sertifikat->status_pengajuan = $request->status_pengajuan;
            
            // Jika status pengajuan disetujui, update juga status utama
            if ($request->status_pengajuan === 'disetujui') {
                $sertifikat->status = 'aktif';
            }
            
            $sertifikat->save();

            return response()->json([
                "status" => "success",
                "message" => "Status pengajuan berhasil diperbarui.",
                "data" => $sertifikat
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat memperbarui status pengajuan",
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