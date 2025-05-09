<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate incoming request
        $fields = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|confirmed',
            'role_id' => 'required|exists:roles,id' // Ensure the role exists
        ]);

        // Create the user with role_id
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'username' => $fields['username'],
            'password' => bcrypt($fields['password']),
            'role_id' => $fields['role_id'] // Add role to user
        ]);

        // Generate API token
        $token = $user->createToken('myapptoken')->plainTextToken;

        // Prepare the response
        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        // Coba cari dari database lokal dulu
        $user = User::where('username', $fields['username'])->first();

        if ($user && Hash::check($fields['password'], $user->password)) {
            $token = $user->createToken('myapptoken')->plainTextToken;

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'token' => $token
            ], 200);
        }

        // Jika tidak ditemukan di lokal, cek ke API eksternal
        try {
            $response = Http::timeout(5)->get('http://127.0.0.1:8001/api/datauser');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghubungi server otentikasi eksternal.',
                'error' => $e->getMessage()
            ], 500);
        }

        if ($response->successful()) {
            $usersFromApi = $response->json('data');

            $matchedUser = collect($usersFromApi)->first(function ($apiUser) use ($fields) {
                return $apiUser['username'] === $fields['username'] &&
                    Hash::check($fields['password'], $apiUser['password']);
            });

            if ($matchedUser) {
                $roleMap = [
                    'Pimpinan Jamaah' => '326f0dde-2851-4e47-ac5a-de6923447317',
                    'Pimpinan Cabang' => '3594bece-a684-4287-b0a2-7429199772a3',
                    'Bidgar Wakaf' => '26b2b64e-9ae3-4e2e-9063-590b1bb00480',
                ];

                $roleId = $roleMap[$matchedUser['name_role']] ?? null;

                if (!$roleId) {
                    // Role tidak terdaftar, tolak akses
                    return response()->json(['message' => 'Akses ditolak: Anda tidak memiliki kepentingan dalam apliaksi ini.'], 403);
                }

                $existingUser = User::where('name', $matchedUser['nama_jamaah'] ?? $matchedUser['username'])->first();

                if (!$existingUser) {
                    $user = User::create([
                        'id' => Str::uuid(),
                        'name' => $matchedUser['nama_jamaah'] ?? $matchedUser['username'],
                        'username' => $matchedUser['username'],
                        'password' => $matchedUser['password'], // Sudah hash
                        'role_id' => $roleId
                    ]);
                } else {
                    if (!Hash::check($fields['password'], $existingUser->password)) {
                        $existingUser->password = $matchedUser['password'];
                    }

                    if ($existingUser->username !== $matchedUser['username']) {
                        $conflictUser = User::where('username', $matchedUser['username'])
                            ->where('id', '!=', $existingUser->id)
                            ->first();

                        if (!$conflictUser) {
                            $existingUser->username = $matchedUser['username'];
                        }
                    }

                    $existingUser->role_id = $roleId;
                    $existingUser->save();

                    $user = $existingUser;
                }

                $token = $user->createToken('myapptoken')->plainTextToken;

                return response()->json([
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role
                    ],
                    'token' => $token
                ], 200);
            }
        }

        return response()->json(['message' => 'Username atau password salah'], 401);
    }


    public function logout(Request $request)
    {
        if (!$request->bearerToken()) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Revoke all tokens for the user
        $user->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}