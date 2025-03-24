<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test method index untuk mengambil daftar pengguna dengan role tertentu.
     *
     * @return void
     */
    public function testIndex()
    {
        // Buat pengguna dummy dengan role_id tertentu
        $roleId = '326f0dde-2851-4e47-ac5a-de6923447317';
        $user = User::create([
            'id' => Str::uuid(), // Generate UUID secara manual
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $roleId,
        ]);

        // Login sebagai pengguna tersebut
        $this->actingAs($user);

        // Buat beberapa pengguna dengan role yang sama
        User::create([
            'id' => Str::uuid(),
            'name' => 'User 1',
            'username' => 'user1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
            'role_id' => $roleId,
        ]);
        User::create([
            'id' => Str::uuid(),
            'name' => 'User 2',
            'username' => 'user2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
            'role_id' => $roleId,
        ]);

        // Panggil endpoint index
        $response = $this->getJson('/api/data/user');

        // Verifikasi respons
        $response->assertStatus(200)
            ->assertJson([
                "status" => "success",
                "message" => "Data pengguna berhasil diambil",
            ])
            ->assertJsonStructure([
                "data" => [
                    '*' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'role_id',
                    ],
                ],
            ]);

        // Verifikasi jumlah data yang dikembalikan
        $this->assertCount(3, $response->json('data')); // 3 pengguna dengan role yang sama
    }

    /**
     * Test method index ketika pengguna tidak terautentikasi.
     *
     * @return void
     */
    public function testIndexUnauthenticated()
    {
        // Panggil endpoint index tanpa login
        $response = $this->getJson('/api/data/user');

        // Verifikasi respons
        $response->assertStatus(401)
            ->assertJson([
                "message" => "Unauthenticated.",
            ]);
    }

    /**
     * Test method show untuk menampilkan detail pengguna berdasarkan ID.
     *
     * @return void
     */
    public function testShow()
    {
        // Tambahkan route sementara
        \Illuminate\Support\Facades\Route::get('/api/data/user/{id}', [\App\Http\Controllers\UserController::class, 'show']);

        // Buat pengguna dummy
        $user = User::create([
            'id' => Str::uuid(), // Generate UUID secara manual
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => '326f0dde-2851-4e47-ac5a-de6923447317',
        ]);

        // Panggil endpoint show dengan ID pengguna yang baru dibuat
        $response = $this->getJson("/api/data/user/{$user->id}");

        // Verifikasi respons
        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role_id' => $user->role_id,
            ]);
    }

    /**
     * Test method show ketika pengguna tidak ditemukan.
     *
     * @return void
     */
    public function testShowNotFound()
    {
        $this->withoutMiddleware();
        // ID yang tidak ada di database
        $nonExistentId = Str::uuid();

        // Panggil endpoint show dengan ID yang tidak ada
        $response = $this->getJson("/api/data/user/{$nonExistentId}");

        // Verifikasi respons
        $response->assertStatus(404)
            ->assertJson([
                'message' => 'User  not found', // Pastikan pesan ini sesuai dengan yang dikembalikan oleh controller
            ]);
    }
}
