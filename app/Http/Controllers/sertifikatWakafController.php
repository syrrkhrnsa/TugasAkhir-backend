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
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'no_dokumen' => 'nullable|string|unique:sertifikats',
            'dokumen' => 'nullable|file|mimes:pdf', // Changed from string to file validation
            'jenis_sertifikat' => 'nullable|string',
            'status_pengajuan' => 'nullable|string',
            'id_tanah' => 'required|uuid',
            'tanggal_pengajuan' => 'required|date', // Fixed validation rule
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi gagal",
                "errors" => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // Rest of your store method remains the same...
        $user = Auth::user();
        if (!$user) {
            return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], Response::HTTP_UNAUTHORIZED);
        }

        $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
        $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
        $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

        if ($user->role_id === $rolePimpinanJamaah) {
            $data = [
                'id_sertifikat' => Str::uuid(),
                'id_tanah' => $request->id_tanah,
                'no_dokumen' => $request->no_dokumen,
                'jenis_sertifikat' => $request->jenis_sertifikat,
                'status_pengajuan' => $request->status_pengajuan,
                'tanggal_pengajuan' => $request->tanggal_pengajuan,
                'status' => 'ditinjau',
                'user_id' => $user->id,
            ];

            // Handle file upload
            if ($request->hasFile('dokumen')) {
                $path = $request->file('dokumen')->store('dokumen', 'public');
                $data['dokumen'] = $path;
            }

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
                $bidgar->notify(new ApprovalNotification($approval, 'create', 'bidgar'));
            }

            return response()->json([
                "status" => "success",
                "message" => "Permintaan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
            ], Response::HTTP_CREATED);
        } else {
            // Jika Pimpinan Cabang atau Bidgar Wakaf
            $data = [
                'id_sertifikat' => Str::uuid(),
                'id_tanah' => $request->id_tanah,
                'no_dokumen' => $request->no_dokumen,
                'jenis_sertifikat' => $request->jenis_sertifikat,
                'status_pengajuan' => $request->status_pengajuan,
                'tanggal_pengajuan' => $request->tanggal_pengajuan,
                'status' => "disetujui",
                'user_id' => $user->id,
            ];

            // Handle file upload
            if ($request->hasFile('dokumen')) {
                $path = $request->file('dokumen')->store('dokumen', 'public');
                $data['dokumen'] = $path;
            }

            $sertifikat = Sertifikat::create($data);

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil ditambahkan dan disetujui.",
                "data" => $sertifikat
            ], Response::HTTP_CREATED);
        }
    } catch (\Exception $e) {
        Log::error('Error storing sertifikat: ' . $e->getMessage());
        return response()->json([
            "status" => "error",
            "message" => "Terjadi kesalahan saat menyimpan data",
            "error" => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

   public function update(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "User tidak terautentikasi"
            ], 401);
        }

        // Role IDs
        $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
        $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

        // Find the specific sertifikat
        $sertifikat = Sertifikat::where('id_sertifikat', $id)
                      ->where('jenis_sertifikat', $request->jenis_sertifikat)
                      ->firstOrFail();

        // Prepare update data
        $updateData = [
            'no_dokumen' => $request->no_dokumen,
            'status_pengajuan' => $request->status_pengajuan,
            'tanggal_pengajuan' => $request->tanggal_pengajuan,
        ];

        // Handle file upload
        if ($request->hasFile('dokumen')) {
            // Delete old file
            if ($sertifikat->dokumen) {
                Storage::delete($sertifikat->dokumen);
            }
            $path = $request->file('dokumen')->store('dokumen', 'public');
            $updateData['dokumen'] = $path;
        }

        // Pimpinan Jamaah workflow
        if ($user->role_id === $rolePimpinanJamaah) {
            // Save original data before update
            $originalData = $sertifikat->getOriginal();
            
            // Create approval first
            $approval = Approval::create([
                'user_id' => $user->id,
                'type' => 'sertifikat_update_'.$sertifikat->jenis_sertifikat,
                'data_id' => $id,
                'status' => 'ditinjau',
                'data' => json_encode([
                    'original' => $originalData,
                    'requested' => $updateData
                ]),
            ]);

            // Update with pending status
            $sertifikat->update(array_merge($updateData, [
                'status' => 'ditinjau'
            ]));

            // Notify Bidgar Wakaf
            User::where('role_id', $roleBidgarWakaf)
                ->each(function($user) use ($approval) {
                    $user->notify(new ApprovalNotification(
                        $approval,
                        'update',
                        'bidgar'
                    ));
                });

            DB::commit();
            return response()->json([
                "status" => "success",
                "message" => "Perubahan menunggu persetujuan Bidgar Wakaf",
                "approval_id" => $approval->id
            ], 202);
        }

        // Bidgar Wakaf direct update
        $sertifikat->update($updateData);
        DB::commit();

        return response()->json([
            "status" => "success",
            "message" => "Data ".$sertifikat->jenis_sertifikat." berhasil diperbarui",
            "data" => $sertifikat
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Sertifikat update error: '.$e->getMessage());
        return response()->json([
            "status" => "error",
            "message" => "Gagal memperbarui data",
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

    /**
 * Remove the specified resource from storage.
 */
public function destroy($id)
{
    try {
        $sertifikat = Sertifikat::find($id);
        
        if (!$sertifikat) {
            return response()->json([
                "status" => "error",
                "message" => "Data tidak ditemukan"
            ], Response::HTTP_NOT_FOUND);
        }

        // Delete associated file if exists
        if ($sertifikat->dokumen) {
            Storage::disk('public')->delete($sertifikat->dokumen);
        }

        $sertifikat->delete();

        return response()->json([
            "status" => "success",
            "message" => "Data berhasil dihapus"
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        Log::error('Error deleting sertifikat: ' . $e->getMessage());
        return response()->json([
            "status" => "error",
            "message" => "Terjadi kesalahan saat menghapus data"
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
}