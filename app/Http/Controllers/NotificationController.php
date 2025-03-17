<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Menampilkan notifikasi yang belum dibaca
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], 401);
        }

        $notifications = $user->unreadNotifications;

        return response()->json([
            "status" => "success",
            "message" => "Notifikasi berhasil diambil",
            "data" => $notifications
        ], 200);
    }

    public function notifications()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], 401);
        }

        // Ambil notifikasi yang belum dibaca
        $notifications = $user->unreadNotifications;

        return response()->json([
            "status" => "success",
            "message" => "Notifikasi berhasil diambil",
            "data" => $notifications
        ], 200);
    }

    // Menandai notifikasi sebagai sudah dibaca
    public function markAsRead($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(["status" => "error", "message" => "User tidak terautentikasi"], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            "status" => "success",
            "message" => "Notifikasi telah ditandai sebagai sudah dibaca",
        ], 200);
    }
}