<?php

namespace App\Http\Controllers;

use App\Models\Sertifikat;
use App\Models\DokumenLegalitas;
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
            $sertifikat = Sertifikat::where('id_tanah', $id_tanah)->get();

            if ($sertifikat->isEmpty()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Data sertifikat untuk tanah ini tidak ditemukan"
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil diambil",
                "data" => $sertifikat
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
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

            $sertifikats = Sertifikat::where('id_tanah', $id_tanah)->get();

            if ($sertifikats->isEmpty()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Data sertifikat untuk tanah ini tidak ditemukan"
                ], Response::HTTP_NOT_FOUND);
            }

            $legalitasData = $sertifikats->map(function ($sertifikat) {
                return [
                    "id_sertifikat" => $sertifikat->id_sertifikat,
                    "jenis_sertifikat" => $sertifikat->jenis_sertifikat
                ];
            });

            return response()->json([
                "status" => "success",
                "message" => "Data legalitas berhasil diambil",
                "data" => [
                    "id_tanah" => $id_tanah,
                    "legalitas" => $legalitasData
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

            $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
            $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

            if ($user->role_id === $rolePimpinanJamaah) {
                $sertifikats = Sertifikat::where('user_id', $user->id)->get();
            } elseif ($user->role_id === $rolePimpinanCabang || $user->role_id === $roleBidgarWakaf) {
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
        $sertifikat = Sertifikat::with('dokumenLegalitas')->find($id);

        if (!$sertifikat) {
            return response()->json(['message' => 'Sertifikat tidak ditemukan'], 404);
        }

        return response()->json($sertifikat);
    }

   public function store(Request $request)
{

     \Log::debug('Received data:', [
        'jenis_sertifikat' => $request->jenis_sertifikat,
        'status_pengajuan' => $request->status_pengajuan,
        'files_count' => $request->hasFile('dokumen') ? count($request->file('dokumen')) : 0
    ]);

    // First validate the non-file fields
    $validator = Validator::make($request->all(), [
        'jenis_sertifikat' => 'required|string|in:BASTW,AIW,SW',
        'status_pengajuan' => 'required|string|in:Diproses,Terbit,Ditolak',
        'tanggal_pengajuan' => 'required|date|before_or_equal:today',
        'id_tanah' => 'required|uuid|exists:tanahs,id_tanah',
        'dokumen' => 'sometimes|array', // Changed from required to sometimes
        'dokumen.*' => 'sometimes|file|mimes:pdf|max:5120',
    ], [
        'jenis_sertifikat.required' => 'Jenis sertifikat wajib diisi',
        'jenis_sertifikat.in' => 'Jenis sertifikat harus salah satu dari: BASTW, AIW, SW',
        'status_pengajuan.required' => 'Status pengajuan wajib diisi',
        'tanggal_pengajuan.required' => 'Tanggal pengajuan wajib diisi',
        'tanggal_pengajuan.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini',
        'id_tanah.exists' => 'Tanah tidak ditemukan',
        'dokumen.*.mimes' => 'Dokumen harus berupa PDF',
        'dokumen.*.max' => 'Ukuran dokumen maksimal 5MB',
    ]);

    if ($validator->fails()) {
        return response()->json([
            "status" => "error",
            "message" => "Validasi data gagal",
            "errors" => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    DB::beginTransaction();
    try {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "User tidak terautentikasi"
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Buat sertifikat
        $sertifikat = Sertifikat::create([
            'id_sertifikat' => Str::uuid(),
            'id_tanah' => $request->id_tanah,
            'jenis_sertifikat' => $request->jenis_sertifikat,
            'status_pengajuan' => $request->status_pengajuan,
            'tanggal_pengajuan' => $request->tanggal_pengajuan,
            'user_id' => $user->id,
            'status' => ($user->role_id === '326f0dde-2851-4e47-ac5a-de6923447317') ? 'ditinjau' : 'disetujui'
        ]);

        // Upload dan simpan dokumen-dokumen jika ada
        if ($request->hasFile('dokumen')) {
            foreach ($request->file('dokumen') as $file) {
                $filename = 'sertifikat/dokumen/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

                Storage::disk('minio')->put($filename, fopen($file->path(), 'r'), [
                    'ContentType' => $file->getMimeType()
                ]);

                DokumenLegalitas::create([
                    'id_dokumen_legalitas' => Str::uuid(),
                    'id_sertifikat' => $sertifikat->id_sertifikat,
                    'dokumen_legalitas' => $filename
                ]);
            }
        }

        // Jika role Pimpinan Jamaah, buat approval
        if ($user->role_id === '326f0dde-2851-4e47-ac5a-de6923447317') {
            $approval = Approval::create([
                'user_id' => $user->id,
                'type' => 'sertifikat',
                'data_id' => $sertifikat->id_sertifikat,
                'data' => json_encode($sertifikat->toArray()),
                'status' => 'ditinjau',
            ]);

            User::where('role_id', '26b2b64e-9ae3-4e2e-9063-590b1bb00480')
                ->each(fn($user) => $user->notify(
                    new ApprovalNotification($approval, 'create', 'bidgar')
                ));

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Permintaan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
                "data" => [
                    "sertifikat" => $sertifikat,
                    "approval_id" => $approval->id
                ]
            ], Response::HTTP_CREATED);
        }

        DB::commit();

        return response()->json([
            "status" => "success",
            "message" => "Sertifikat berhasil dibuat",
            "data" => $sertifikat
        ], Response::HTTP_CREATED);

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Error creating sertifikat', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);

        return response()->json([
            "status" => "error",
            "message" => "Gagal menyimpan data sertifikat",
            "error" => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            if (app()->environment('testing') && $request->has('force_db_error')) {
                throw new \Exception('DB Simulated Error');
            }
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    "status" => "error",
                    "message" => "User tidak terautentikasi"
                ], Response::HTTP_UNAUTHORIZED);
            }

            $validator = Validator::make($request->all(), [
                'tanggal_pengajuan' => 'required|date_format:Y-m-d',
                'no_dokumen' => 'nullable|string|max:100',
            ], [
                'tanggal_pengajuan.required' => 'Tanggal pengajuan wajib diisi',
                'tanggal_pengajuan.date_format' => 'Format tanggal harus YYYY-MM-DD',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Validasi gagal",
                    "errors" => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

            $sertifikat = Sertifikat::where('id_sertifikat', $id)->firstOrFail();

            $updateData = [
                'no_dokumen' => $request->no_dokumen ?? $sertifikat->no_dokumen,
                'tanggal_pengajuan' => $request->tanggal_pengajuan,
            ];

            // Pimpinan Jamaah workflow
            if ($user->role_id === $rolePimpinanJamaah) {
                $originalData = $sertifikat->getOriginal();

                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'sertifikat_update',
                    'data_id' => $id,
                    'status' => 'ditinjau',
                    'data' => json_encode([
                        'previous_data' => $originalData,
                        'updated_data' => $updateData
                    ]),
                ]);

                $sertifikat->update(array_merge($updateData, [
                    'status' => 'ditinjau'
                ]));

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
                ], Response::HTTP_ACCEPTED);
            }

            // Bidgar Wakaf direct update
            $sertifikat->update($updateData);
            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Data sertifikat berhasil diperbarui",
                "data" => $sertifikat
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sertifikat update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'sertifikat_id' => $id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                "status" => "error",
                "message" => "Gagal memperbarui data",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            $user = Auth::user();
            if (!$user) {
                return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], Response::HTTP_UNAUTHORIZED);
            }

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

            $sertifikat = Sertifikat::find($id);
            if (!$sertifikat) {
                return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
            }

            $sertifikat->status_pengajuan = $request->status_pengajuan;
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

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $sertifikat = Sertifikat::find($id);

            if (!$sertifikat) {
                return response()->json([
                    "status" => "error",
                    "message" => "Data tidak ditemukan"
                ], Response::HTTP_NOT_FOUND);
            }

            // Hapus semua dokumen terkait
            $dokumenList = DokumenLegalitas::where('id_sertifikat', $id)->get();
            foreach ($dokumenList as $dokumen) {
                if (Storage::disk('minio')->exists($dokumen->dokumen_legalitas)) {
                    Storage::disk('minio')->delete($dokumen->dokumen_legalitas);
                }
                $dokumen->delete();
            }

            $sertifikat->delete();

            DB::commit();
            return response()->json([
                "status" => "success",
                "message" => "Data berhasil dihapus"
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting sertifikat: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat menghapus data"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downloadDokumen($id_dokumen_legalitas)
    {
        $dokumen = DokumenLegalitas::findOrFail($id_dokumen_legalitas);

        if (!Storage::disk('minio')->exists($dokumen->dokumen_legalitas)) {
            abort(404, 'File tidak ditemukan');
        }

        return Storage::disk('minio')->download(
            $dokumen->dokumen_legalitas,
            "dokumen_legalitas_{$dokumen->id_dokumen_legalitas}.pdf"
        );
    }

    public function viewDokumen($id_dokumen_legalitas)
    {
        $dokumen = DokumenLegalitas::findOrFail($id_dokumen_legalitas);

        if (!Storage::disk('minio')->exists($dokumen->dokumen_legalitas)) {
            abort(404, 'File tidak ditemukan');
        }

        // Tambahkan header CORS dan pastikan token valid
        return response()->make(
            Storage::disk('minio')->get($dokumen->dokumen_legalitas),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$dokumen->dokumen_legalitas.'"',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
            ]
        );
    }

    public function uploadDokumen(Request $request, $id_sertifikat)
    {


        $validator = Validator::make($request->all(), [
            'dokumen.*' => 'required|file|mimes:pdf|max:5120',
        ], [
            'dokumen.*.required' => 'Dokumen wajib diupload',
            'dokumen.*.mimes' => 'Dokumen harus berupa PDF',
            'dokumen.*.max' => 'Ukuran dokumen maksimal 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => "Validasi file gagal",
                "errors" => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            if (app()->environment('testing') && $request->has('force_db_error')) {
                throw new \Exception('DB Simulated Error');
            }

            $sertifikat = Sertifikat::findOrFail($id_sertifikat);
            $uploadedFiles = [];

            foreach ($request->file('dokumen') as $file) {
                $filename = 'sertifikat/dokumen/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

                Storage::disk('minio')->put($filename, fopen($file->path(), 'r'), [
                    'ContentType' => $file->getMimeType()
                ]);

                $dokumenLegalitas = DokumenLegalitas::create([
                    'id_dokumen_legalitas' => Str::uuid(),
                    'id_sertifikat' => $id_sertifikat,
                    'dokumen_legalitas' => $filename
                ]);

                $uploadedFiles[] = [
                    "id_dokumen_legalitas" => $dokumenLegalitas->id_dokumen_legalitas,
                    "filename" => $filename,
                    "url" => Storage::disk('minio')->url($filename)
                ];
            }

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Dokumen berhasil diupload",
                "data" => $uploadedFiles
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error uploading document', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                "status" => "error",
                "message" => "Gagal mengupload dokumen",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteDokumen($id_dokumen_legalitas)
    {
        DB::beginTransaction();
        try {
            if (app()->environment('testing') && request()->has('force_db_error')) {
                throw new \Exception('DB error simulated');
            }
            $dokumen = DokumenLegalitas::find($id_dokumen_legalitas);

            if (!$dokumen) {
                // Jika dokumen sudah tidak ada, anggap sebagai sukses
                return response()->json([
                    "status" => "success",
                    "message" => "Dokumen sudah tidak ada"
                ], Response::HTTP_OK);
            }

            if (Storage::disk('minio')->exists($dokumen->dokumen_legalitas)) {
                Storage::disk('minio')->delete($dokumen->dokumen_legalitas);
            }

            $dokumen->delete();

            DB::commit();

            return response()->json([
                "status" => "success",
                "message" => "Dokumen berhasil dihapus"
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting document', [
                'error' => $e->getMessage(),
                'id_dokumen_legalitas' => $id_dokumen_legalitas
            ]);

            return response()->json([
                "status" => "error",
                "message" => "Gagal menghapus dokumen",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDokumenLegalitas($id_sertifikat)
    {
        try {
            if (app()->environment('testing') && request()->has('force_db_error')) {
                throw new \Exception('DB Simulated Error');
            }
            $sertifikat = Sertifikat::with('dokumenLegalitas')->findOrFail($id_sertifikat);

            return response()->json([
                "status" => "success",
                "message" => "Dokumen legalitas berhasil diambil",
                "data" => $sertifikat->dokumenLegalitas->map(function($dokumen) {
                    return [
                        'id_dokumen_legalitas' => $dokumen->id_dokumen_legalitas,
                        'filename' => $dokumen->dokumen_legalitas,
                        'url' => Storage::disk('minio')->url($dokumen->dokumen_legalitas),
                        'created_at' => $dokumen->created_at
                    ];
                })
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Gagal mengambil dokumen legalitas",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDokumenList($id_sertifikat)
    {
        try {
            if (app()->environment('testing') && request()->has('force_db_error')) {
                throw new \Exception('Database error for testing');
            }
            $dokumenList = DokumenLegalitas::where('id_sertifikat', $id_sertifikat)
                ->get()
                ->map(function($dokumen) {
                    return [
                        'id' => $dokumen->id_dokumen_legalitas,
                        'name' => basename($dokumen->dokumen_legalitas),
                        'url' => Storage::disk('minio')->url($dokumen->dokumen_legalitas),
                        'created_at' => $dokumen->created_at->format('Y-m-d H:i:s')
                    ];
                });

            return response()->json([
                "status" => "success",
                "message" => "Daftar dokumen berhasil diambil",
                "data" => $dokumenList
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Error fetching document list', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                "status" => "error",
                "message" => "Gagal mengambil daftar dokumen",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
