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
            'username' => 'user' , // Ensure username is set
        ]);

        // Prepare login data
        $data = [
            'email' => $user->email,
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
    }

    /** @test */
    public function it_returns_bad_credentials_when_login_fails()
    {
        // Attempt to login with wrong credentials
        $data = [
            'email' => 'nonexistentuser@example.com',
            'password' => 'wrongpassword',
        ];

        // Send a POST request to login
        $response = $this->postJson('/api/login', $data);

        // Assert 401 status for bad credentials
        $response->assertStatus(401);

        // Assert error message
        $response->assertJson([
            'message' => 'Bad credentials',
        ]);
    }

    /** @test */
    public function it_can_logout_a_user()
    {
        $role = Role::factory()->create();
        // Create and login a user
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'testppuser', // Ensure username is set
        ]);

        // Login user and get token
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $response->json()['token'];

        // Send a POST request to logout
        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // Assert successful logout response
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logged out successfully']);

        // Assert the user is logged out by checking if tokens are deleted
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
}
