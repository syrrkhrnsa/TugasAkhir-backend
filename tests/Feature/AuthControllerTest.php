<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;


class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_register_a_user()
    {
        // Membuat role untuk pengguna
        $role = Role::factory()->create();

        // Menyimulasikan data permintaan
        $userData = [// Mengatur UUID secara manual
            'name' => 'Test User',
            'username' => 'testppuser',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $role->id,
        ];

        // Membuat permintaan POST untuk registrasi
        $response = $this->postJson('/api/register', $userData);

        // Memastikan pengguna berhasil dibuat
        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'username',
                    'name',
                    'email',
                    'role_id',
                ],
                'token',
            ]);

        // Memastikan pengguna ada di database
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);
    }

    /** @test */
    public function it_can_login_a_user()
    {
        $role = Role::factory()->create(); // Create a role for the user
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
            'username' => 'testuser', // Pastikan username di-set
        ]);

        // Prepare login data yang sesuai dengan validasi di controller
        $data = [
            'username' => 'testuser', // Gunakan username bukan email
            'password' => 'password123',
        ];

        // Send a POST request to login
        $response = $this->postJson('/api/login', $data);

        // Assert successful response with 200 status
        $response->assertStatus(200);

        // Assert the response contains user data and token
        $response->assertJsonStructure([
            'user' => ['id', 'username', 'name', 'email', 'role'],
            'token'
        ]);

        // Verifikasi data user yang dikembalikan
        $response->assertJson([
            'user' => [
                'username' => 'testuser',
                'name' => $user->name,
            ]
        ]);
    }

    /** @test */
    public function it_returns_bad_credentials_when_login_fails()
    {
        // Mock external API to return empty response
        Http::fake([
            'http://127.0.0.1:8001/api/datauser' => Http::response(['data' => []], 200)
        ]);

        // Attempt to login with wrong credentials
        $data = [
            'username' => 'nonexistentuser',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/login', $data);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Username atau password salah'
            ]);
    }

    /** @test */
    public function it_can_logout_a_user()
    {
        $role = Role::factory()->create();
        // Create user with known password
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'testuser',
            'password' => Hash::make('password123'), // Set password explicitly
        ]);

        // Login user and get token - using correct credentials
        $loginResponse = $this->postJson('/api/login', [
            'username' => 'testuser', // Use username instead of email
            'password' => 'password123', // Use correct password
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('token');

        // Send logout request with the token
        $logoutResponse = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // Assert successful logout response
        $logoutResponse->assertStatus(200);
        $logoutResponse->assertJson(['message' => 'Logged out successfully']);

        // Refresh user instance from database
        $user->refresh();

        // Assert the user's tokens were deleted
        $this->assertCount(0, $user->tokens);
    }

    /** @test */
    public function it_returns_error_if_token_not_provided_on_logout()
    {
        // Send logout request without bearer token
        $response = $this->postJson('/api/logout');

        // Assert 401 status for missing token
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function it_returns_error_if_user_is_unauthenticated_on_logout()
    {
        // Send logout request when the user is not authenticated
        $this->withoutMiddleware();

        $response = $this->postJson('/api/logout');

        // Assert 401 status for unauthenticated user
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Token not provided']);
    }

    public function test_login_handles_external_auth_failure()
    {
        // Mock Http facade to throw exception
        Http::fake([
            'http://127.0.0.1:8001/api/datauser' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        // Create a user that doesn't exist locally
        $loginData = [
            'username' => 'externaluser',
            'password' => 'externalpass'
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Gagal menghubungi server otentikasi eksternal.',
                'error' => 'Connection timeout'
            ]);
    }

    public function test_external_auth_success_with_new_user()
    {
        // Setup roles if not already exist
        Role::firstOrCreate(
            ['id' => '326f0dde-2851-4e47-ac5a-de6923447317'],
            ['name' => 'Pimpinan Jamaah']
        );
        Role::firstOrCreate(
            ['id' => '3594bece-a684-4287-b0a2-7429199772a3'],
            ['name' => 'Pimpinan Cabang']
        );
        Role::firstOrCreate(
            ['id' => '26b2b64e-9ae3-4e2e-9063-590b1bb00480'],
            ['name' => 'Bidgar Wakaf']
        );

        // Mock external API response
        $hashedPassword = Hash::make('password123');

        Http::fake([
            'http://127.0.0.1:8001/api/datauser' => Http::response([
                'data' => [
                    [
                        'username' => 'external_user',
                        'password' => $hashedPassword,
                        'nama_jamaah' => 'External User',
                        'name_role' => 'Pimpinan Jamaah'
                    ]
                ]
            ], 200)
        ]);

        // Hit the login endpoint
        $response = $this->postJson('/api/login', [
            'username' => 'external_user',
            'password' => 'password123'
        ]);

        // Check response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'username', 'name', 'email', 'role'],
                'token'
            ])
            ->assertJson([
                'user' => [
                    'username' => 'external_user',
                    'name' => 'External User',
                    'role' => [
                        'id' => '326f0dde-2851-4e47-ac5a-de6923447317',
                        'name' => 'Pimpinan Jamaah'
                    ]
                ]
            ]);

        // Verify user created in local database
        $this->assertDatabaseHas('users', [
            'username' => 'external_user',
            'name' => 'External User',
            'role_id' => '326f0dde-2851-4e47-ac5a-de6923447317'
        ]);
    }

    public function test_external_auth_success_with_existing_user()
    {
        // Setup roles
        Role::firstOrCreate(
            ['id' => '3594bece-a684-4287-b0a2-7429199772a3'],
            ['name' => 'Pimpinan Cabang']
        );

        // Create existing user with old password and username
        $oldHashedPassword = Hash::make('old_password');
        $user = User::factory()->create([
            'name' => 'Existing User',
            'username' => 'old_username',
            'password' => $oldHashedPassword,
            'role_id' => '3594bece-a684-4287-b0a2-7429199772a3'
        ]);

        // Mock external API with updated info (new username & password)
        $newHashedPassword = Hash::make('new_password');
        Http::fake([
            'http://127.0.0.1:8001/api/datauser' => Http::response([
                'data' => [
                    [
                        'username' => 'new_username',
                        'password' => $newHashedPassword,
                        'nama_jamaah' => 'Existing User',
                        'name_role' => 'Pimpinan Cabang'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'new_username',
            'password' => 'new_password'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'username' => 'new_username',
                    'name' => 'Existing User'
                ]
            ]);

        // Refresh user from database
        $user->refresh();
        $this->assertEquals('new_username', $user->username);
        $this->assertTrue(Hash::check('new_password', $user->password));
    }

}
