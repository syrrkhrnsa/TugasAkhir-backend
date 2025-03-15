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

class TanahController extends Controller
{
    
    public function publicIndex()
    {
        try {
            // Ambil hanya data dengan status "disetujui"
            $tanah = Tanah::where('status', 'disetujui')->get();

            return response()->json([
                "status" => "success",
                "message" => "Data tanah berhasil diambil",
                "data" => $tanah
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat mengambil data tanah untuk publik', [
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
                'id_tanah' => Str::uuid(),
                'NamaPimpinanJamaah' => $request->NamaPimpinanJamaah,
                'NamaWakif' => $request->NamaWakif,
                'lokasi' => $request->lokasi,
                'luasTanah' => $request->luasTanah,
                'legalitas' => 'N/A',
            ];

            Approval::create([
                'user_id' => $user->id,
                'type' => 'tanah',
                'data_id' => Str::uuid(),
                'data' => json_encode($data),
                'status' => 'ditinjau',
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Permintaan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
            ], Response::HTTP_CREATED);

            $bidgarWakaf = User::where('role_id', '26b2b64e-9ae3-4e2e-9063-590b1bb00480')->get();
            foreach ($bidgarWakaf as $bidgar) {
            $bidgar->notify(new ApprovalNotification($approval));
            }
        } else {
            // Jika Pimpinan Cabang atau Bidgar Wakaf, langsung simpan ke tabel Tanah
            $tanah = Tanah::create([
                'id_tanah' => Str::uuid(),
                'NamaPimpinanJamaah' => $request->NamaPimpinanJamaah,
                'NamaWakif' => $request->NamaWakif,
                'lokasi' => $request->lokasi,
                'luasTanah' => $request->luasTanah,
                'legalitas' => 'N/A',
                'status' => 'disetujui',
                'user_id' => $user->id,
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Data tanah berhasil ditambahkan dan disetujui.",
                "data" => $tanah
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

        // Store the previous data
        $previousData = $tanah->toArray();

        // ID role
        $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
        $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
        $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

        if ($user->role_id === $rolePimpinanJamaah) {
            // If Pimpinan Jamaah, update saved as Approval
            $data = [
                'id_tanah' => $tanah->id_tanah,
                'legalitas' => $request->legalitas ?? $tanah->legalitas,
                'NamaPimpinanJamaah' => $request->NamaPimpinanJamaah ?? $tanah->NamaPimpinanJamaah,
                'NamaWakif' => $request->NamaWakif ?? $tanah->NamaWakif,
                'lokasi' => $request->lokasi ?? $tanah->lokasi,
                'luasTanah' => $request->luasTanah ?? $tanah->luasTanah,
            ];

            // Create an approval with both previous and updated data
            Approval::create([
                'user_id' => $user->id,
                'type' => 'tanah_update',
                'data_id' => $tanah->id_tanah,
                'data' => json_encode([
                    'previous_data' => $previousData,
                    'updated_data' => $data
                ]),
                'status' => 'ditinjau',
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Permintaan pembaruan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
            ], Response::HTTP_CREATED);

            // $bidgarWakaf = User::where('role_id', '26b2b64e-9ae3-4e2e-9063-590b1bb00480')->get();
            // foreach ($bidgarWakaf as $bidgar) {
            //     $bidgar->notify(new ApprovalNotification($approval));
            // }
        } else {
            // If Pimpinan Cabang or Bidgar Wakaf, update data directly
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