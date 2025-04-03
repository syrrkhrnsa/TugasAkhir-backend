<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Sertifikat;

class ActivityLogController extends Controller
{
    /**
     * Get logs related to Tanah changes.
     */
    public function logTanah()
    {
        $logs = ActivityLog::where('model_type', 'App\\Models\\Tanah')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                $changes = json_decode($log->changes, true);
    
                return [
                    'nama_user' => $log->user->name ?? 'Unknown',
                    'aksi' => ucfirst($log->action),
                    'perubahan' => array_filter($changes),
                    'waktu' => $log->created_at->format('H:i:s'),
                    'tanggal' => $log->created_at->format('Y-m-d'),
                ];
            });
    
        return response()->json($logs);
    }

    /**
     * Get logs related to Sertifikat changes.
     */
    public function logSertifikat()
    {
        $logs = ActivityLog::where('model_type', 'App\\Models\\Sertifikat')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                $changes = json_decode($log->changes, true);

                return [
                    'nama_user' => $log->user->name ?? 'Unknown',
                    'perubahan' => ucfirst($log->action) . " data sertifikat di bagian " . implode(', ', array_keys($changes)),
                    'waktu' => $log->created_at->format('H:i:s'),
                    'tanggal' => $log->created_at->format('Y-m-d'),
                ];
            });

        return response()->json($logs);
    }

    /**
     * Get logs related to Status changes.
     */
    public function logStatus()
    {
        $logs = ActivityLog::where('action', 'update')
            ->whereRaw("JSON_CONTAINS(changes, '\"status\"', '$')")
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'nama_user' => $log->user->name ?? 'Unknown',
                    'aksi' => 'Update Status',
                    'perubahan' => json_decode($log->changes, true),
                    'waktu' => $log->created_at->format('H:i:s'),
                    'tanggal' => $log->created_at->format('Y-m-d'),
                ];
            });

        return response()->json($logs);
    }

    /**
     * Get logs by specific user.
     */
    public function logByUser($userId)
    {
        $logs = ActivityLog::where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'nama_user' => $log->user->name ?? 'Unknown',
                    'aksi' => ucfirst($log->action),
                    'model' => class_basename($log->model_type),
                    'perubahan' => json_decode($log->changes, true),
                    'waktu' => $log->created_at->format('H:i:s'),
                    'tanggal' => $log->created_at->format('Y-m-d'),
                ];
            });

        return response()->json($logs);
    }

    public function logByTanahId($tanahId)
{
    // Normalize UUID
    $normalizedId = strtolower(trim($tanahId, '"\' '));
    
    // Validate UUID format
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $normalizedId)) {
        return response()->json([
            'error' => 'Format ID tanah tidak valid',
            'received_id' => $tanahId,
            'normalized_id' => $normalizedId
        ], 400);
    }

    $logs = ActivityLog::where('model_type', 'App\\Models\\Tanah')
        ->where('model_id', $normalizedId)
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($log) {
            $changes = is_string($log->changes) ? 
                json_decode($log->changes, true) ?? ['raw' => $log->changes] : 
                $log->changes;

            return [
                'id' => $log->id,
                'model_id' => $log->model_id,
                'nama_user' => $log->user->name ?? 'Unknown',
                'aksi' => ucfirst($log->action),
                'perubahan' => $changes,
                'waktu' => $log->created_at->format('H:i:s'),
                'tanggal' => $log->created_at->format('Y-m-d'),
            ];
        });

    if ($logs->isEmpty()) {
        return response()->json([
            'error' => 'Data log tidak ditemukan',
            'message' => 'Tidak ada aktivitas yang tercatat untuk tanah ini'
        ], 404);
    }

    return response()->json($logs);
}

    public function logBySertifikatId($sertifikatId)
{
    // Normalisasi UUID - hilangkan tanda petik, spasi, dan convert ke lowercase
    $normalizedId = strtolower(trim($sertifikatId, '"\' '));
    
    // Validasi format UUID
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $normalizedId)) {
        return response()->json([
            'error' => 'Format ID sertifikat tidak valid',
            'received_id' => $sertifikatId,
            'normalized_id' => $normalizedId
        ], 400);
    }

    $logs = ActivityLog::where('model_type', 'App\\Models\\Sertifikat')
        ->where(function($query) use ($normalizedId) {
            $query->where('model_id', $normalizedId)
                  ->orWhere('model_id', 'like', '%'.$normalizedId.'%');
        })
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($log) {
            // Handle kemungkinan format changes yang berbeda
            $changes = $log->changes;
            
            // Coba decode berbagai kemungkinan format
            if (is_string($changes)) {
                $changes = json_decode($changes, true) ?? 
                          json_decode(stripslashes($changes), true) ?? 
                          ['raw_changes' => $changes];
            }

            return [
                'id' => $log->id,
                'model_id' => $log->model_id,
                'nama_user' => $log->user->name ?? 'Unknown',
                'aksi' => ucfirst($log->action),
                'model' => 'Sertifikat',
                'perubahan' => $changes,
                'waktu' => $log->created_at->format('H:i:s'),
                'tanggal' => $log->created_at->format('Y-m-d'),
            ];
        });

    if ($logs->isEmpty()) {
        // Debug query
        $debugQuery = ActivityLog::where('model_type', 'App\\Models\\Sertifikat')
            ->where(function($query) use ($normalizedId) {
                $query->where('model_id', $normalizedId)
                      ->orWhere('model_id', 'like', '%'.$normalizedId.'%');
            })
            ->toSql();
            
        return response()->json([
            'error' => 'Data log tidak ditemukan',
            'debug' => [
                'input_id' => $sertifikatId,
                'normalized_id' => $normalizedId,
                'sql_query' => $debugQuery,
                'sample_data' => ActivityLog::where('model_type', 'App\\Models\\Sertifikat')
                                    ->select('model_id')
                                    ->limit(5)
                                    ->get()
                                    ->toArray()
            ]
        ], 404);
    }

    return response()->json($logs);
}

    /**
     * Get all logs for Tanah and Sertifikat
     */
    public function logSemuaTanahDanSertifikat()
    {
        $logs = ActivityLog::whereIn('model_type', [
                'App\\Models\\Tanah',
                'App\\Models\\Sertifikat'
            ])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($log) {
                $changes = json_decode($log->changes, true);
                $modelType = class_basename($log->model_type);

                return [
                    'id' => $log->id,
                    'model_id' => $log->model_id,
                    'nama_user' => $log->user->name ?? 'Unknown',
                    'aksi' => ucfirst($log->action),
                    'model' => $modelType,
                    'perubahan' => $changes,
                    'waktu' => $log->created_at->format('H:i:s'),
                    'tanggal' => $log->created_at->format('Y-m-d'),
                ];
            });

        return response()->json($logs);
    }
}