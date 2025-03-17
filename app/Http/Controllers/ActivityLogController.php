<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;

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
                    'perubahan' => array_filter($changes), // Hapus perubahan kosong
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
}
