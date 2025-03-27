<?php

namespace App\Http\Controllers;

use App\Models\Tanah;
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
use App\Models\Sertifikat;
use Illuminate\Support\Facades\DB;

class TanahController extends Controller
{

    public function publicIndex()
    {
        $tanah = Tanah::where('status', 'disetujui')->get();

        return response()->json([
            "status" => "success",
            "message" => "Data tanah berhasil diambil",
            "data" => $tanah
        ], Response::HTTP_OK);
    }

    public function index()
    {
        try {
            // Ambil user yang sedang login
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
                $tanah = Tanah::where('user_id', $user->id)->get();
            } elseif ($user->role_id === $rolePimpinanCabang || $user->role_id === $roleBidgarWakaf) {
                // Pimpinan Cabang dan Bidgar Wakaf: hanya melihat data dengan status "disetujui"
                $tanah = Tanah::where('status', 'disetujui')->get();
            } else {
                Log::error('Akses ditolak untuk user', ['user_id' => $user->id, 'role_id' => $user->role_id]);
                return response()->json(["status" => "error", "message" => "Anda tidak memiliki izin untuk melihat data"], Response::HTTP_FORBIDDEN);
            }

            return response()->json([
                "status" => "success",
                "message" => "Data tanah berhasil diambil",
                "data" => $tanah
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat mengambil data tanah', [
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


    // GET: Ambil data tanah berdasarkan ID
    public function show($id)
    {
        $tanah = Tanah::find($id);
        if (!$tanah) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }
        return response()->json(["status" => "success", "data" => $tanah], Response::HTTP_OK);
    }

    // POST: Tambah data tanah baru
    public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'NamaPimpinanJamaah' => 'required|string',
            'NamaWakif' => 'required|string',
            'lokasi' => 'required|string',
            'luasTanah' => 'required|string',
            'noDokumenBastw' => 'nullable|string|unique:sertifikats',
            'noDokumenAIW' => 'nullable|string|unique:sertifikats',
            'noDokumenSW' => 'nullable|string|unique:sertifikats',
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
        $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

        // Buat ID tanah dan ID sertifikat
        $idTanah = Str::uuid();
        $idSertifikat = Str::uuid();

        $id_tanah = Str::uuid();
        $id_sertifikat = Str::uuid();

        $dataTanah = [
            'id_tanah' => $id_tanah,
            'NamaPimpinanJamaah' => $request->NamaPimpinanJamaah,
            'NamaWakif' => $request->NamaWakif,
            'lokasi' => $request->lokasi,
            'luasTanah' => $request->luasTanah,
            'legalitas' => 'N/A',
        ];

        $dataSertifikat = [
            'id_sertifikat' => $id_sertifikat,
            'id_tanah' => $id_tanah,
            'noDokumenBastw' => $request->noDokumenBastw,
            'noDokumenAIW' => $request->noDokumenAIW,
            'noDokumenSW' => $request->noDokumenSW,
            'legalitas' => 'N/A',
            'dokBastw' => $request->dokBastw,
            'dokAiw' => $request->dokAiw,
            'dokSw' => $request->dokSw,
        ];

        if ($user->role_id === $rolePimpinanJamaah) {
            // Jika Pimpinan Jamaah, data disimpan ke tabel Approval
            $approval = Approval::create([
                'user_id' => $user->id,
                'type' => 'tanah_dan_sertifikat',
                'data_id' => Str::uuid(),
                'data' => json_encode([
                    'tanah' => $dataTanah,
                    'sertifikat' => $dataSertifikat,
                ]),
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
            // Jika Pimpinan Cabang atau Bidgar Wakaf, langsung simpan ke tabel Tanah dan Sertifikat
            $tanah = null;
            $sertifikat = null;

            DB::transaction(function () use ($dataTanah, $dataSertifikat, $user, &$tanah, &$sertifikat) {
                $tanah = Tanah::create(array_merge($dataTanah, ['status' => 'disetujui', 'user_id' => $user->id]));
                $sertifikat = Sertifikat::create(array_merge($dataSertifikat, ['status' => 'disetujui', 'user_id' => $user->id]));
            });

            return response()->json([
                "status" => "success",
                "message" => "Data tanah dan sertifikat berhasil ditambahkan dan disetujui.",
                "data" => [
                    'tanah' => $tanah,
                    'sertifikat' => $sertifikat,
                ]
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

    // PUT: Update data tanah
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'NamaPimpinanJamaah' => 'sometimes|string',
                'NamaWakif' => 'sometimes|string',
                'lokasi' => 'sometimes|string',
                'luasTanah' => 'sometimes|string',
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

            $tanah = Tanah::find($id);
            if (!$tanah) {
                return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
            }

            // Simpan data sebelumnya
            $previousData = $tanah->toArray();

            // ID role
            $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
            $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

            if ($user->role_id === $rolePimpinanJamaah) {
                // Jika Pimpinan Jamaah, update disimpan sebagai Approval
                $data = [
                    'id_tanah' => $tanah->id_tanah,
                    'NamaPimpinanJamaah' => $request->NamaPimpinanJamaah ?? $tanah->NamaPimpinanJamaah,
                    'NamaWakif' => $request->NamaWakif ?? $tanah->NamaWakif,
                    'lokasi' => $request->lokasi ?? $tanah->lokasi,
                    'luasTanah' => $request->luasTanah ?? $tanah->luasTanah,
                ];

                $tanah->update(['status' => 'ditinjau']);

                // Buat approval dengan data sebelumnya dan data yang diperbarui
                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'tanah_update',
                    'data_id' => $tanah->id_tanah,
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
                $tanah->update($request->all());

                return response()->json([
                    "status" => "success",
                    "message" => "Data tanah berhasil diperbarui.",
                    "data" => $tanah
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
                'legalitas' => 'sometimes|string',
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

            $tanah = Tanah::find($id);
            if (!$tanah) {
                return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
            }

            // Update data legalitas
            $tanah->update([
                'legalitas' => $request->legalitas ?? $tanah->legalitas,
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Data legalitas berhasil diperbarui.",
                "data" => $tanah
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan saat memperbarui data legalitas",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // DELETE: Hapus data tanah
    public function destroy($id)
    {
        $tanah = Tanah::find($id);
        if (!$tanah) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }

        $tanah->delete();
        return response()->json(["status" => "success", "message" => "Data berhasil dihapus"], Response::HTTP_OK);
    }
}
