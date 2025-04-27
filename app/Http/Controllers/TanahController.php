<?php

namespace App\Http\Controllers;

use App\Models\Tanah;
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

    // Method baru untuk menampilkan detail tanah tertentu tanpa login
    public function publicShow($id)
    {
        try {
            $tanah = Tanah::with('sertifikats')->find($id);
            
            if (!$tanah) {
                return response()->json([
                    "status" => "error", 
                    "message" => "Data tanah tidak ditemukan"
                ], Response::HTTP_NOT_FOUND);
            }
            
            if ($tanah->status !== 'disetujui') {
                return response()->json([
                    "status" => "error", 
                    "message" => "Data tanah tidak tersedia untuk publik"
                ], Response::HTTP_FORBIDDEN);
            }
            
            return response()->json([
                "status" => "success",
                "message" => "Detail tanah berhasil diambil",
                "data" => $tanah
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            Log::error('Error mengambil detail tanah publik: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan server"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Method untuk mencari tanah berdasarkan lokasi secara publik
    public function publicSearch(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'keyword' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Validasi gagal",
                    "errors" => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $keyword = $request->keyword;
            
            $tanah = Tanah::where('status', 'disetujui')
                ->where(function($query) use ($keyword) {
                    $query->where('lokasi', 'like', "%{$keyword}%")
                          ->orWhere('NamaPimpinanJamaah', 'like', "%{$keyword}%")
                          ->orWhere('jenis_tanah', 'like', "%{$keyword}%");
                })
                ->get();
                
            return response()->json([
                "status" => "success",
                "message" => "Pencarian tanah berhasil",
                "data" => $tanah
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            Log::error('Error pencarian tanah publik: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan server"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Method untuk mendapatkan tanah berdasarkan jenis secara publik
    public function publicByJenis($jenisTanah)
    {
        try {
            $tanah = Tanah::where('status', 'disetujui')
                    ->where('jenis_tanah', $jenisTanah)
                    ->get();
                    
            return response()->json([
                "status" => "success",
                "message" => "Data tanah berdasarkan jenis berhasil diambil",
                "data" => $tanah
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            Log::error('Error mengambil tanah berdasarkan jenis publik: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan server"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Method untuk mendapatkan tanah berdasarkan pimpinan jamaah secara publik
    public function publicByPimpinan($namaPimpinan)
    {
        try {
            $tanah = Tanah::where('status', 'disetujui')
                    ->where('NamaPimpinanJamaah', $namaPimpinan)
                    ->get();
                    
            return response()->json([
                "status" => "success",
                "message" => "Data tanah berdasarkan pimpinan jamaah berhasil diambil",
                "data" => $tanah
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            Log::error('Error mengambil tanah berdasarkan pimpinan publik: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan server"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], 401);
            }

            $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
            $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

            $query = Tanah::query();

            if ($user->role_id === $rolePimpinanJamaah) {
                $query->where('NamaPimpinanJamaah', $user->name);
            } 
            elseif ($user->role_id === $rolePimpinanCabang || $user->role_id === $roleBidgarWakaf) {
                $query->whereIn('status', ['disetujui', 'ditinjau']);
            } 
            else {
                return response()->json(["status" => "error", "message" => "Akses ditolak"], 403);
            }

            $tanah = $query->get();

            return response()->json([
                "status" => "success",
                "message" => "Data tanah berhasil diambil",
                "data" => $tanah
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error mengambil data tanah: ' . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Terjadi kesalahan server"
            ], 500);
        }
    }

    public function show($id)
    {
        $tanah = Tanah::find($id);
        if (!$tanah) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }
        return response()->json(["status" => "success", "data" => $tanah], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        try {
            if (app()->environment('testing') && request()->has('force_db_error')) {
                throw new \Exception('Database error for testing');
            }
            
            $validator = Validator::make($request->all(), [
                'NamaPimpinanJamaah' => 'required|string',
                'NamaWakif' => 'required|string',
                'lokasi' => 'required|string',
                'luasTanah' => 'required|string',
                'jenis_tanah' => 'nullable|string',
                'batas_timur' => 'nullable|string',
                'batas_selatan' => 'nullable|string',
                'batas_barat' => 'nullable|string',
                'batas_utara' => 'nullable|string',
                'panjang_tanah' => 'nullable|string',
                'lebar_tanah' => 'nullable|string',
                'catatan' => 'nullable|string',
                'alamat_wakif' => 'nullable|string',
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

            $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';
            $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';

            $data = [
                'id_tanah' => Str::uuid(),
                'NamaPimpinanJamaah' => $request->NamaPimpinanJamaah,
                'NamaWakif' => $request->NamaWakif,
                'lokasi' => $request->lokasi,
                'luasTanah' => $request->luasTanah,
                'legalitas' => '-',
                'jenis_tanah' => $request->jenis_tanah,
                'batas_timur' => $request->batas_timur,
                'batas_selatan' => $request->batas_selatan,
                'batas_barat' => $request->batas_barat,
                'batas_utara' => $request->batas_utara,
                'panjang_tanah' => $request->panjang_tanah,
                'lebar_tanah' => $request->lebar_tanah,
                'catatan' => $request->catatan,
                'alamat_wakif' => $request->alamat_wakif,
            ];

            if ($user->role_id === $rolePimpinanJamaah) {
                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'tanah',
                    'data_id' => Str::uuid(),
                    'data' => json_encode($data),
                    'status' => 'ditinjau',
                ]);

                $bidgarWakaf = User::where('role_id', $roleBidgarWakaf)->get();
                foreach ($bidgarWakaf as $bidgar) {
                    $bidgar->notify(new ApprovalNotification($approval, 'create', 'bidgar'));
                }

                return response()->json([
                    "status" => "success",
                    "message" => "Permintaan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
                ], Response::HTTP_CREATED);
            } else {
                $tanah = Tanah::create(array_merge($data, [
                    'status' => 'disetujui',
                    'user_id' => $user->id,
                ]));

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

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'NamaPimpinanJamaah' => 'sometimes|string',
                'NamaWakif' => 'sometimes|string',
                'lokasi' => 'sometimes|string',
                'luasTanah' => 'sometimes|string',
                'jenis_tanah' => 'nullable|string',
                'batas_timur' => 'nullable|string',
                'batas_selatan' => 'nullable|string',
                'batas_barat' => 'nullable|string',
                'batas_utara' => 'nullable|string',
                'panjang_tanah' => 'nullable|string',
                'lebar_tanah' => 'nullable|string',
                'catatan' => 'nullable|string',
                'alamat_wakif' => 'nullable|string',
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

            $previousData = $tanah->toArray();

            $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
            $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
            $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

            $data = [
                'id_tanah' => $tanah->id_tanah,
                'NamaPimpinanJamaah' => $request->NamaPimpinanJamaah ?? $tanah->NamaPimpinanJamaah,
                'NamaWakif' => $request->NamaWakif ?? $tanah->NamaWakif,
                'lokasi' => $request->lokasi ?? $tanah->lokasi,
                'luasTanah' => $request->luasTanah ?? $tanah->luasTanah,
                'jenis_tanah' => $request->jenis_tanah ?? $tanah->jenis_tanah,
                'batas_timur' => $request->batas_timur ?? $tanah->batas_timur,
                'batas_selatan' => $request->batas_selatan ?? $tanah->batas_selatan,
                'batas_barat' => $request->batas_barat ?? $tanah->batas_barat,
                'batas_utara' => $request->batas_utara ?? $tanah->batas_utara,
                'panjang_tanah' => $request->panjang_tanah ?? $tanah->panjang_tanah,
                'lebar_tanah' => $request->lebar_tanah ?? $tanah->lebar_tanah,
                'catatan' => $request->catatan ?? $tanah->catatan,
                'alamat_wakif' => $request->alamat_wakif ?? $tanah->alamat_wakif,
            ];

            if ($user->role_id === $rolePimpinanJamaah) {
                $tanah->update(['status' => 'ditinjau']);

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

                $bidgarWakaf = User::where('role_id', $roleBidgarWakaf)->get();
                foreach ($bidgarWakaf as $bidgar) {
                    $bidgar->notify(new ApprovalNotification($approval, 'update', 'bidgar'));
                }

                return response()->json([
                    "status" => "success",
                    "message" => "Permintaan pembaruan telah dikirim ke Bidgar Wakaf untuk ditinjau.",
                ], Response::HTTP_CREATED);
            } else {
                $tanah->update($data);

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

    public function destroy($id)
    {
        $tanah = Tanah::find($id);
        if (!$tanah) {
            return response()->json(["status" => "error", "message" => "Data tidak ditemukan"], Response::HTTP_NOT_FOUND);
        }

        $tanah->sertifikats()->delete();
        $tanah->delete();
        
        return response()->json(["status" => "success", "message" => "Data berhasil dihapus"], Response::HTTP_OK);
    }
}