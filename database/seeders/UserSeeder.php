<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
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
        User::create([
            'name' => 'syira',
            'email' => 'syira@test.com',
            'password' => Hash::make('12345678'), // Jangan lupa ganti password sesuai kebutuhan
        ]);

        User::create([
            'name' => 'agista',
            'email' => 'agista@test.com',
            'password' => Hash::make('12345678'),
        ]);

        User::create([
            'name' => 'reno',
            'email' => 'reno@test.com',
            'password' => Hash::make('12345678'),
        ]);

    }
}