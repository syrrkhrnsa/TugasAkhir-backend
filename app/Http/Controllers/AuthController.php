<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

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
    // Validasi incoming request
    $fields = $request->validate([
        'email' => 'required|string',
        'password' => 'required|string'
    ]);

    // Cek jika user ada berdasarkan email
    $user = User::where('email', $fields['email'])->first();

    // Periksa password
    if (!$user || !Hash::check($fields['password'], $user->password)) {
        return response([
            'message' => 'Bad credentials'
        ], 401);
    }

    // Memuat relasi 'role' saat login
    $user->load('role');

    // Generate token API
    $token = $user->createToken('myapptoken')->plainTextToken;

    // Menyiapkan response
    $response = [
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role, // Menyertakan role dalam response
        ],
        'token' => $token
    ];

    return response($response, 200);
}

    public function logout(Request $request)
    {
        if (!$request->bearerToken()) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Revoke all tokens for the user
        $user->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
