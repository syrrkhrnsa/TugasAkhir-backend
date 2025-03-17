<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ambil ID Role
        $roles = DB::table('roles')->pluck('id', 'name');

        DB::table('users')->insert([
            [
                'id' => '550e8400-e29b-41d4-a716-446655440001', // UUID statis
                'name' => 'Pimpinan Jamaah',
                'username' => 'pimpinan_jamaah',
                'email' => 'pimpinanjamaah1@example.com',
                'password' => Hash::make('12345678'),
                'role_id' => $roles['Pimpinan Jamaah'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440002', // UUID statis
                'name' => 'Pimpinan Jamaah2',
                'username' => 'pimpinan_jamaah2',
                'email' => 'pimpinanjamaah2@example.com',
                'password' => Hash::make('12345678'),
                'role_id' => $roles['Pimpinan Jamaah'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440003', // UUID statis
                'name' => 'Pimpinan Cabang',
                'username' => 'pimpinan_cabang',
                'email' => 'cabang@example.com',
                'password' => Hash::make('12345678'),
                'role_id' => $roles['Pimpinan Cabang'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440004', // UUID statis
                'name' => 'Bidgar Wakaf',
                'username' => 'bidgar_wakaf',
                'email' => 'wakaf@example.com',
                'password' => Hash::make('12345678'),
                'role_id' => $roles['Bidgar Wakaf'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}