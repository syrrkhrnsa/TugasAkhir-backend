<?php

namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use App\Models\Tanah;
use App\Http\Controllers\TanahController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use App\Models\Role;
use App\Notifications\ApprovalNotification;

class TanahControllerTest extends TestCase
{
    use RefreshDatabase;

    private $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
    private $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
    private $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Notification::fake();


    }

    public function test_index_returns_json_response()
    {
        $this->withoutMiddleware(); // Menonaktifkan middleware untuk keperluan pengujian

        // Buat pengguna palsu
        $user = User::factory()->create();  // Buat user menggunakan factory

        // Simulasikan login sebagai pengguna
        $this->actingAs($user, 'sanctum'); // Pastikan menggunakan guard 'sanctum'

        // Menyediakan data valid untuk permintaan
        $data = [
            'NamaPimpinanJamaah' => 'Nama Pimpinan',
            'NamaWakif' => 'Nama Wakif',
            'lokasi' => 'Lokasi Tanah',
            'luasTanah' => '100',
        ];

        // Kirimkan data dalam permintaan POST
        $response = $this->postJson('/api/tanah', $data);

        // Memastikan status code 200 (berhasil)
        $response->assertStatus(201);
    }




    public function test_store_creates_new_tanah()
    {
        $this->withoutMiddleware();  // Menonaktifkan middleware jika perlu
        // Buat pengguna palsu
        $user = User::factory()->create();  // Buat user menggunakan factory

        // Simulasikan login sebagai pengguna
        $this->actingAs($user, 'sanctum');

        // Menyediakan data valid untuk permintaan
        $data = [
            'NamaPimpinanJamaah' => 'Tanah A',
            'luasTanah' => '100',
            'NamaWakif' => 'Test',
            'lokasi' => 'jalan A',
        ];

        // Kirimkan data dalam permintaan POST
        $response = $this->postJson('/api/tanah', $data);

        // Memastikan status code 201 (berhasil dibuat)
        $response->assertStatus(201);

        // Memastikan data yang dikirimkan disimpan di database (jika perlu)
        $this->assertDatabaseHas('tanahs', [
            'NamaPimpinanJamaah' => 'Tanah A',
            'luasTanah' => '100',
            'NamaWakif' => 'Test',
            'lokasi' => 'jalan A',
        ]);
    }


    public function test_update_modifies_existing_tanah()
    {
        $this->withoutMiddleware(); // Disable middleware if necessary

        // Buat pengguna dengan role Pimpinan Cabang atau Bidgar Wakaf (yang bisa langsung update)
        $user = User::factory()->create([
            'role_id' => '3594bece-a684-4287-b0a2-7429199772a3' // Pimpinan Cabang
        ]);

        $this->actingAs($user, 'sanctum');

        // Buat data tanah yang ada di database
        $tanah = Tanah::create([
            'id_tanah' => Str::uuid(),
            'NamaPimpinanJamaah' => 'Tanah A',
            'NamaWakif' => 'Wakif A',
            'lokasi' => 'jalan A',
            'luasTanah' => '100',
            'legalitas' => 'N/A',
            'status' => 'ab',
            'user_id' => $user->id
        ]);

        // Data yang akan diupdate
        $data = [
            'NamaPimpinanJamaah' => 'Tanah B',
            'NamaWakif' => 'Wakif B',
            'lokasi' => 'jalan B',
            'luasTanah' => '150',
            'legalitas' => 'N/A',
            'status' => 'ab',
            'user_id' => $user->id
        ];

        // Kirimkan permintaan PUT untuk update
        $response = $this->putJson("/api/tanah/{$tanah->id_tanah}", $data);

        // Memastikan status code 200 (berhasil)
        $response->assertStatus(200);

        // Memastikan data yang diperbarui ada di database
        $this->assertDatabaseHas('tanahs', [
            'id_tanah' => $tanah->id_tanah,
            'NamaPimpinanJamaah' => 'Tanah B',
            'NamaWakif' => 'Wakif B',
            'lokasi' => 'jalan B',
            'luasTanah' => '150',
            'legalitas' => 'N/A',
            'status' => 'ab',
            'user_id' => $user->id
        ]);
    }



    public function test_destroy_returns_error_for_nonexistent_tanah()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $nonExistentUuid = Str::uuid();

        $response = $this->deleteJson("/api/tanah/{$nonExistentUuid}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ]);
    }

    public function test_public_index_returns_approved_tanah()
    {
        $approvedTanah = Tanah::factory()->create(['status' => 'disetujui']);
        $pendingTanah = Tanah::factory()->create(['status' => 'ditinjau']);

        $response = $this->getJson('/api/tanah/public');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_tanah', (string)$approvedTanah->id_tanah);
    }

    public function test_index_for_pimpinan_jamaah()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);
        $tanah = Tanah::factory()->create(['user_id' => $user->id]);
        Tanah::factory()->create(); // Other tanah not belonging to user

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_tanah', (string)$tanah->id_tanah); // Cast to string
    }

    public function test_index_for_pimpinan_cabang()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanCabang]);
        $approvedTanah = Tanah::factory()->create(['status' => 'disetujui']);
        Tanah::factory()->create(['status' => 'ditinjau']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_tanah', (string)$approvedTanah->id_tanah);
    }

    public function test_index_for_unauthorized_role()
    {
        // First create a role that won't have access
        $unauthorizedRole = \App\Models\Role::create([
            'id' => '00000000-0000-0000-0000-000000000000',
            'name' => 'Unauthorized Role'
        ]);

        $user = User::factory()->create([
            'role_id' => $unauthorizedRole->id
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(403);
    }

    public function test_show_existing_tanah()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        $tanah = Tanah::factory()->create();

        $response = $this->getJson("/api/tanah/{$tanah->id_tanah}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id_tanah', (string)$tanah->id_tanah);
    }

    public function test_store_by_pimpinan_jamaah_creates_approval()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);

        $data = [
            'NamaPimpinanJamaah' => 'Test Name',
            'NamaWakif' => 'Test Wakif',
            'lokasi' => 'Test Location',
            'luasTanah' => '100',
            'noDokumenBastw' => '123',
            'noDokumenAIW' => '456',
            'noDokumenSW' => '789'
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tanah', $data);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('approvals', [
            'type' => 'tanah_dan_sertifikat',
            'status' => 'ditinjau'
        ]);
    }

    public function test_store_by_bidgar_wakaf_creates_tanah_directly()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        $data = [
            'NamaPimpinanJamaah' => 'Test Name',
            'NamaWakif' => 'Test Wakif',
            'lokasi' => 'Test Location',
            'luasTanah' => '100',
            'noDokumenBastw' => '123',
            'noDokumenAIW' => '456',
            'noDokumenSW' => '789'
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tanah', $data);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('tanahs', [
            'NamaPimpinanJamaah' => 'Test Name',
            'status' => 'disetujui'
        ]);
    }

    public function test_store_validation_errors()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tanah', []);

        $response->assertStatus(400)
            ->assertJsonValidationErrors([
                'NamaPimpinanJamaah',
                'NamaWakif',
                'lokasi',
                'luasTanah'
            ]);
    }

    public function test_update_by_pimpinan_jamaah_creates_approval()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);
        $tanah = Tanah::factory()->create(['user_id' => $user->id]);

        $data = [
            'NamaPimpinanJamaah' => 'Updated Name',
            'NamaWakif' => 'Updated Wakif'
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tanah/{$tanah->id_tanah}", $data);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('approvals', [
            'type' => 'tanah_update',
            'status' => 'ditinjau'
        ]);
    }

    public function test_update_by_bidgar_wakaf_updates_directly()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $tanah = Tanah::factory()->create();

        $data = [
            'NamaPimpinanJamaah' => 'Updated Name',
            'NamaWakif' => 'Updated Wakif'
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tanah/{$tanah->id_tanah}", $data);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('tanahs', [
            'id_tanah' => $tanah->id_tanah,
            'NamaPimpinanJamaah' => 'Updated Name'
        ]);
    }

    public function test_update_nonexistent_tanah()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tanah/" . Str::uuid(), ['NamaPimpinanJamaah' => 'Test']);

        $response->assertStatus(404);
    }

    public function test_update_legalitas()
    {
        $user = User::factory()->create();
        $tanah = Tanah::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tanah/legalitas/{$tanah->id_tanah}", [
                'legalitas' => 'SHM'
            ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('tanahs', [
            'id_tanah' => $tanah->id_tanah,
            'legalitas' => 'SHM'
        ]);
    }

    public function test_destroy_existing_tanah()
    {
        $user = User::factory()->create();
        $tanah = Tanah::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/tanah/{$tanah->id_tanah}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('tanahs', ['id_tanah' => $tanah->id_tanah]);
    }

    public function test_destroy_nonexistent_tanah()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/tanah/" . Str::uuid());

        $response->assertStatus(404);
    }

    public function test_public_index_returns_only_approved_tanah()
    {
        $approved = Tanah::factory()->create(['status' => 'disetujui']);
        $pending = Tanah::factory()->create(['status' => 'ditinjau']);

        $response = $this->getJson('/api/tanah/public');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id_tanah' => (string)$approved->id_tanah])
            ->assertJsonMissing(['id_tanah' => (string)$pending->id_tanah]);
    }

    public function test_index_for_pimpinan_jamaah_shows_only_their_data()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);
        $tanah = Tanah::factory()->create(['user_id' => $user->id]);
        Tanah::factory()->create(); // Other tanah

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id_tanah' => (string)$tanah->id_tanah]);
    }

    public function test_index_for_bidgar_wakaf_shows_only_approved()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $approved = Tanah::factory()->create(['status' => 'disetujui']);
        $pending = Tanah::factory()->create(['status' => 'ditinjau']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id_tanah' => (string)$approved->id_tanah])
            ->assertJsonMissing(['id_tanah' => (string)$pending->id_tanah]);
    }

    public function test_index_for_unauthorized_role_returns_403()
    {
        $unauthorizedRole = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $unauthorizedRole->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki izin untuk melihat data']);
    }

    public function test_store_with_database_error_handling()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        // Mock database error
        $this->mock(Tanah::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \Exception('Database error'));
        });

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/tanah/data', [
                'NamaPimpinanJamaah' => 'Test',
                'NamaWakif' => 'Test',
                'lokasi' => 'Test',
                'luasTanah' => '100'
            ]);

        $response->assertStatus(500)
            ->assertJson(['status' => 'error']);
    }

    public function test_index_returns_unauthenticated_for_guest()
    {
        $response = $this->getJson('/api/tanah');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_index_handles_exception()
    {
        $this->withoutMiddleware(); // Nonaktifkan semua middleware

        // Mock the Auth facade to throw exception when user() is called
        Auth::shouldReceive('user')
            ->once()
            ->andThrow(new \Exception('Auth error'));

        $response = $this->getJson('/api/tanah');

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data'
            ]);
    }

    public function test_store_returns_validation_errors()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tanah', [
                'NamaPimpinanJamaah' => '', // invalid
                'NamaWakif' => '', // invalid
                'lokasi' => '', // invalid
                'luasTanah' => '' // invalid
            ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors([
                'NamaPimpinanJamaah',
                'NamaWakif',
                'lokasi',
                'luasTanah'
            ]);
    }

    public function test_store_handles_exception()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        // Mock DB to throw exception
        DB::shouldReceive('transaction')->andThrow(new \Exception('Database error'));

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tanah', [
                'NamaPimpinanJamaah' => 'Test',
                'NamaWakif' => 'Test',
                'lokasi' => 'Test',
                'luasTanah' => '100'
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data'
            ]);
    }

    public function test_update_returns_validation_errors()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);
        $tanah = Tanah::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tanah/{$tanah->id_tanah}", [
                'luasTanah' => 100 // bukan string
            ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['luasTanah']);
    }
}
