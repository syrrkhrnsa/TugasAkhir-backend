<?php

namespace App\Http\Controllers;

use App\Models\Sertifikat;
use App\Models\Approval;
use App\Notifications\ApprovalNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SertifikatWakafController extends Controller
{
    // Role constants
    const ROLE_PIMPINAN_JAMAAH = '326f0dde-2851-4e47-ac5a-de6923447317';
    const ROLE_BIDGAR_WAKAF = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';
    const ROLE_PIMPINAN_CABANG = '3594bece-a684-4287-b0a2-7429199772a3';

    public function publicIndex()
    {
        try {
            $sertifikats = Sertifikat::where('status', 'disetujui')->get();
            return $this->successResponse($sertifikats);
        } catch (\Exception $e) {
            Log::error('Error in publicIndex: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function getSertifikatByIdTanah($id_tanah)
    {
        try {
            $sertifikat = Sertifikat::where('id_tanah', $id_tanah)->get();
            
            if ($sertifikat->isEmpty()) {
                return $this->notFoundError('Data sertifikat tidak ditemukan');
            }

            return $this->successResponse($sertifikat);
        } catch (\Exception $e) {
            Log::error('Error in getSertifikatByIdTanah: ' . $e->getMessage());
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
                $sertifikats = Sertifikat::where('user_id', $user->id)->get();
            } elseif (in_array($user->role_id, [self::ROLE_PIMPINAN_CABANG, self::ROLE_BIDGAR_WAKAF])) {
                $sertifikats = Sertifikat::where('status', 'disetujui')->get();
            } else {
                return $this->forbiddenError();
            }

            return $this->successResponse($sertifikats);
        } catch (\Exception $e) {
            Log::error('Error in index: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $sertifikat = Sertifikat::find($id);
            if (!$sertifikat) {
                return $this->notFoundError('Sertifikat tidak ditemukan');
            }
            return $this->successResponse($sertifikat);
        } catch (\Exception $e) {
            Log::error('Error in show: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_tanah' => 'required|uuid|exists:tanahs,id_tanah',
                'no_dokumen' => 'nullable|string|unique:sertifikats',
                'dokumen' => 'nullable|string',
                'jenis_sertifikat' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedError();
            }

            $data = [
                'id_sertifikat' => Str::uuid(),
                'id_tanah' => $request->id_tanah,
                'no_dokumen' => $request->no_dokumen ?? '-',
                'dokumen' => $request->dokumen ?? '-',
                'jenis_sertifikat' => $request->jenis_sertifikat ?? '-',
                'status_pengajuan' => 'diajukan',
                'status' => 'ditinjau',
                'legalitas' => 'N/A',
                'user_id' => $user->id
            ];

            if ($user->role_id === self::ROLE_PIMPINAN_JAMAAH) {
                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'sertifikat',
                    'data_id' => $data['id_sertifikat'],
                    'data' => json_encode($data),
                    'status' => 'ditinjau',
                ]);

                $this->notifyBidgarWakaf($approval, 'create');
                return $this->successResponse(null, 'Pengajuan sertifikat dikirim untuk approval', Response::HTTP_CREATED);
            } else {
                $data['status'] = 'disetujui';
                $data['status_pengajuan'] = 'disetujui';
                $sertifikat = Sertifikat::create($data);
                return $this->successResponse($sertifikat, 'Sertifikat berhasil dibuat dan disetujui', Response::HTTP_CREATED);
            }
        } catch (\Exception $e) {
            Log::error('Error in store: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->unauthorizedError();
            }

            $sertifikat = Sertifikat::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'no_dokumen' => 'nullable|string|unique:sertifikats,no_dokumen,' . $id . ',id_sertifikat',
                'dokumen' => 'nullable|string',
                'jenis_sertifikat' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $updateData = $request->only(['no_dokumen', 'dokumen', 'jenis_sertifikat']);

            if ($user->role_id === self::ROLE_PIMPINAN_JAMAAH) {
                $previousData = $sertifikat->toArray();
                
                // Set default values for empty fields
                foreach ($updateData as $key => $value) {
                    if (empty($value)) {
                        $updateData[$key] = '-';
                    }
                }

                $sertifikat->fill($updateData);
                $sertifikat->status = 'ditinjau';
                $sertifikat->save();

                $approval = Approval::create([
                    'user_id' => $user->id,
                    'type' => 'sertifikat_update',
                    'data_id' => $sertifikat->id_sertifikat,
                    'data' => json_encode([
                        'previous_data' => $previousData,
                        'updated_data' => $updateData,
                        'id_sertifikat' => $sertifikat->id_sertifikat
                    ]),
                    'status' => 'ditinjau',
                ]);

                $this->notifyBidgarWakaf($approval, 'update');
                return $this->successResponse(null, 'Permintaan update dikirim untuk approval');
            } else {
                $sertifikat->update($updateData);
                return $this->successResponse($sertifikat, 'Sertifikat berhasil diperbarui');
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
                'legalitas' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $sertifikat = Sertifikat::find($id);
            if (!$sertifikat) {
                return $this->notFoundError('Sertifikat tidak ditemukan');
            }

            $sertifikat->update(['legalitas' => $request->legalitas]);
            return $this->successResponse($sertifikat, 'Legalitas berhasil diperbarui');
        } catch (\Exception $e) {
            Log::error('Error in updateLegalitas: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $sertifikat = Sertifikat::find($id);
            if (!$sertifikat) {
                return $this->notFoundError('Sertifikat tidak ditemukan');
            }

            $sertifikat->delete();
            return $this->successResponse(null, 'Sertifikat berhasil dihapus');
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
            "message" => "Terjadi kesalahan",
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
            "message" => "Unauthorized"
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function forbiddenError()
    {
        return response()->json([
            "status" => "error",
            "message" => "Forbidden"
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