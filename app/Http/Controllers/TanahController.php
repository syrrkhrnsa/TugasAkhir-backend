<?php

namespace App\Http\Controllers;

use App\Models\Tanah;
use App\Models\Approval;
use App\Notifications\ApprovalNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TanahController extends Controller
{
    // Role constants for cleaner code
    const ROLE_PIMPINAN_JAMAAH = '326f0dde-2851-4e47-ac5a-de6923447317';
    const ROLE_BIDGAR_WAKAF = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';
    const ROLE_PIMPINAN_CABANG = '3594bece-a684-4287-b0a2-7429199772a3';

    public function publicIndex()
    {
        try {
            $tanah = Tanah::where('status', 'disetujui')->get();
            return $this->successResponse($tanah, 'Data tanah berhasil diambil');
        } catch (\Exception $e) {
            Log::error('Error in publicIndex: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function index()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedError();
            }

            if ($user->role_id === self::ROLE_PIMPINAN_JAMAAH) {
                $tanah = Tanah::where('user_id', $user->id)->get();
            } elseif ($user->role_id === self::ROLE_PIMPINAN_CABANG || $user->role_id === self::ROLE_BIDGAR_WAKAF) {
                $tanah = Tanah::where('status', 'disetujui')->get();
            } else {
                return $this->forbiddenError();
            }

            return $this->successResponse($tanah, 'Data tanah berhasil diambil');
        } catch (\Exception $e) {
            Log::error('Error in index: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $tanah = Tanah::find($id);
            if (!$tanah) {
                return $this->notFoundError('Data tanah tidak ditemukan');
            }
            return $this->successResponse($tanah);
        } catch (\Exception $e) {
            Log::error('Error in show: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'NamaPimpinanJamaah' => 'required|string|max:255',
                'NamaWakif' => 'required|string|max:255',
                'lokasi' => 'required|string',
                'luasTanah' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedError();
            }

            $dataTanah = [
                'id_tanah' => Str::uuid(),
                'NamaPimpinanJamaah' => $request->NamaPimpinanJamaah,
                'NamaWakif' => $request->NamaWakif,
                'lokasi' => $request->lokasi,
                'luasTanah' => $request->luasTanah,
                'legalitas' => 'N/A',
                'user_id' => $user->id,
            ];

            if ($user->role_id === self::ROLE_PIMPINAN_JAMAAH) {
                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'tanah',  // Changed from 'tanah_dan_sertifikat' to 'tanah'
                    'data_id' => $dataTanah['id_tanah'],
                    'data' => json_encode($dataTanah),
                    'status' => 'ditinjau',
                ]);

                $this->notifyBidgarWakaf($approval, 'create');

                return $this->successResponse(null, 'Permintaan telah dikirim ke Bidgar Wakaf untuk ditinjau.', Response::HTTP_CREATED);
            } else {
                $dataTanah['status'] = 'disetujui';
                $tanah = Tanah::create($dataTanah);
                return $this->successResponse($tanah, 'Data tanah berhasil ditambahkan.', Response::HTTP_CREATED);
            }
        } catch (\Exception $e) {
            Log::error('Error in store: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'NamaPimpinanJamaah' => 'sometimes|string|max:255',
                'NamaWakif' => 'sometimes|string|max:255',
                'lokasi' => 'sometimes|string',
                'luasTanah' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedError();
            }

            $tanah = Tanah::find($id);
            if (!$tanah) {
                return $this->notFoundError('Data tanah tidak ditemukan');
            }

            if ($user->role_id === self::ROLE_PIMPINAN_JAMAAH) {
                $previousData = $tanah->toArray();
                $updatedData = $request->only([
                    'NamaPimpinanJamaah',
                    'NamaWakif',
                    'lokasi',
                    'luasTanah'
                ]);

                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'tanah_update',
                    'data_id' => $tanah->id_tanah,
                    'data' => json_encode([
                        'previous_data' => $previousData,
                        'updated_data' => $updatedData
                    ]),
                    'status' => 'ditinjau',
                ]);

                $this->notifyBidgarWakaf($approval, 'update');

                return $this->successResponse(null, 'Permintaan pembaruan dikirim ke Bidgar Wakaf.');
            } else {
                $tanah->update($request->all());
                return $this->successResponse($tanah, 'Data tanah berhasil diperbarui.');
            }
        } catch (\Exception $e) {
            Log::error('Error in update: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function updateLegalitas(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'legalitas' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $tanah = Tanah::find($id);
            if (!$tanah) {
                return $this->notFoundError('Data tanah tidak ditemukan');
            }

            $tanah->update(['legalitas' => $request->legalitas]);
            
            return $this->successResponse($tanah, 'Legalitas berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error in updateLegalitas: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $tanah = Tanah::find($id);
            if (!$tanah) {
                return $this->notFoundError('Data tanah tidak ditemukan');
            }

            $tanah->delete();
            return $this->successResponse(null, 'Data tanah berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Error in destroy: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    // Helper Methods
    private function notifyBidgarWakaf($approval, $action)
    {
        $bidgarUsers = User::where('role_id', self::ROLE_BIDGAR_WAKAF)->get();
        foreach ($bidgarUsers as $user) {
            $user->notify(new ApprovalNotification($approval, $action, 'bidgar'));
        }
    }

    private function successResponse($data = null, $message = 'Success', $code = Response::HTTP_OK)
    {
        return response()->json([
            "status" => "success",
            "message" => $message,
            "data" => $data
        ], $code);
    }

    private function errorResponse($error, $code = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        return response()->json([
            "status" => "error",
            "message" => "Terjadi kesalahan internal",
            "error" => $error
        ], $code);
    }

    private function validationError($errors)
    {
        return response()->json([
            "status" => "error",
            "message" => "Validasi gagal",
            "errors" => $errors
        ], Response::HTTP_BAD_REQUEST);
    }

    private function unauthorizedError()
    {
        return response()->json([
            "status" => "error",
            "message" => "User tidak terautentikasi"
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function forbiddenError()
    {
        return response()->json([
            "status" => "error",
            "message" => "Akses ditolak"
        ], Response::HTTP_FORBIDDEN);
    }

    private function notFoundError($message)
    {
        return response()->json([
            "status" => "error",
            "message" => $message
        ], Response::HTTP_NOT_FOUND);
    }
}