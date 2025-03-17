<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Mendapatkan pengguna yang sedang login
        $user = Auth::user();

        // Memeriksa apakah pengguna terautentikasi
        if (!$user) {
            return response()->json(["status" => "error", "message" => "User  tidak terautentikasi"], 401);
        }

        // Mengambil semua data pengguna yang memiliki role sebagai pimpinan jamaah
        $users = User::where('role_id', '326f0dde-2851-4e47-ac5a-de6923447317')->get();

        // Mengembalikan data pengguna dalam format JSON
        return response()->json([
            "status" => "success",
            "message" => "Data pengguna berhasil diambil",
            "data" => $users
        ], 200);
    }
    /**
     * Display the specified user.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Mencari pengguna berdasarkan ID
        $user = User::find($id);

        // Jika pengguna tidak ditemukan, kembalikan respons 404
        if (!$user) {
            return response()->json(['message' => 'User  not found'], 404);
        }

        // Mengembalikan data pengguna dalam format JSON
        return response()->json($user);
    }
}