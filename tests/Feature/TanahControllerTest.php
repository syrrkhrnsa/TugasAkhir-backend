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
use App\Models\Sertifikat;
use Symfony\Component\HttpFoundation\Response;
use App\Notifications\ApprovalNotification;
use Illuminate\Support\Facades\Log;

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
        $tanah = Tanah::factory()->create([
            'NamaPimpinanJamaah' => $user->name // Sesuaikan dengan field yang difilter di controller
        ]);
        Tanah::factory()->create(); // Other tanah not belonging to user

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_tanah', (string)$tanah->id_tanah);
    }

    public function test_index_for_pimpinan_cabang()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanCabang]);

        // Create tanah with approved and reviewed status
        $approvedTanah = Tanah::factory()->create(['status' => 'disetujui']);
        $reviewedTanah = Tanah::factory()->create(['status' => 'ditinjau']);

        // Create tanah with other status that should not be included
        Tanah::factory()->create(['status' => 'ditolak']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Expecting 2 data (disetujui and ditinjau)
            ->assertJsonFragment(['id_tanah' => (string)$approvedTanah->id_tanah])
            ->assertJsonFragment(['id_tanah' => (string)$reviewedTanah->id_tanah]);
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

        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tanah', $data);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('approvals', [
            'type' => 'tanah',
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
        $user = User::factory()->create([
            'role_id' => $this->rolePimpinanJamaah,
            'name' => 'Pimpinan Jamaah Test' // Pastikan nama konsisten
        ]);

        // Tanah milik user ini (sesuai nama)
        $tanah = Tanah::factory()->create([
            'NamaPimpinanJamaah' => $user->name
        ]);

        // Tanah lain yang tidak seharusnya muncul
        Tanah::factory()->create([
            'NamaPimpinanJamaah' => 'Nama Lain'
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id_tanah' => (string)$tanah->id_tanah]);
    }

    public function test_index_for_bidgar_wakaf_shows_only_approved()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        // Data yang seharusnya muncul
        $approved = Tanah::factory()->create(['status' => 'disetujui']);
        $pending = Tanah::factory()->create(['status' => 'ditinjau']);

        // Data yang tidak seharusnya muncul
        $rejected = Tanah::factory()->create(['status' => 'ditolak']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data') // Harapkan 2 data (disetujui dan ditinjau)
            ->assertJsonFragment(['id_tanah' => (string)$approved->id_tanah])
            ->assertJsonFragment(['id_tanah' => (string)$pending->id_tanah])
            ->assertJsonMissing(['id_tanah' => (string)$rejected->id_tanah]);
    }

    public function test_index_for_unauthorized_role_returns_403()
    {
        $unauthorizedRole = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $unauthorizedRole->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tanah');

        $response->assertStatus(403)
            ->assertJson([
                "status" => "error",
                "message" => "Akses ditolak"
            ]);
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
        $this->withoutMiddleware(); // Disable all middleware

        // Mock the Auth facade to throw exception when user() is called
        Auth::shouldReceive('user')
            ->once()
            ->andThrow(new \Exception('Auth error'));

        $response = $this->getJson('/api/tanah');

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server' // Match controller's message
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

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tanah', [
                'NamaPimpinanJamaah' => 'Test',
                'NamaWakif' => 'Test',
                'lokasi' => 'Test',
                'luasTanah' => '100',
                'force_db_error' => true // This triggers the exception in your controller
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => 'Database error for testing'
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

    public function test_update_legalitas_validation_errors()
    {
        $user = User::factory()->create();
        $tanah = Tanah::factory()->create();

        // Test with invalid data (empty legalitas)
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tanah/legalitas/{$tanah->id_tanah}", [
                'legalitas' => '', // empty string should fail validation
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validasi gagal'
            ])
            ->assertJsonValidationErrors(['legalitas']);
    }



    public function test_update_legalitas_not_found()
    {
        $user = User::factory()->create();
        $nonExistentId = Str::uuid();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tanah/legalitas/{$nonExistentId}", [
                'legalitas' => 'SHM'
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ]);
    }

    public function test_update_legalitas_success()
    {
        $user = User::factory()->create();
        $tanah = Tanah::factory()->create(['legalitas' => '-']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tanah/legalitas/{$tanah->id_tanah}", [
                'legalitas' => 'SHM'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data legalitas berhasil diperbarui.',
                'data' => [
                    'legalitas' => 'SHM'
                ]
            ]);

        $this->assertDatabaseHas('tanahs', [
            'id_tanah' => $tanah->id_tanah,
            'legalitas' => 'SHM'
        ]);
    }

    public function test_public_show_returns_tanah_details_when_approved()
    {
        // Create an approved tanah record
        $tanah = Tanah::factory()->create([
            'status' => 'disetujui'
        ]);

        // Create a certificate associated with this tanah
        $sertifikat = Sertifikat::factory()->withTanah($tanah)->create([
            'id_tanah' => $tanah->id_tanah,
            // Make sure we're only using fields that exist in the database
            // Remove 'dokumen' field if it doesn't exist in your DB
            // If you have a newer schema, adjust accordingly
        ]);

        // Make the request to the publicShow endpoint
        $response = $this->getJson("/api/tanah/public/{$tanah->id_tanah}");

        // Assert the response is successful and contains the expected data
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Detail tanah berhasil diambil',
                'data' => [
                    'id_tanah' => (string)$tanah->id_tanah,
                    'status' => 'disetujui'
                ]
            ]);

        // Check that the sertifikats relation is included
        // Update this line to match your actual JSON structure and field names
        $response->assertJsonPath('data.sertifikats.0.id_sertifikat', (string)$sertifikat->id_sertifikat);
    }

    public function test_public_show_returns_404_for_nonexistent_tanah()
    {
        // Generate a random UUID that doesn't exist in the database
        $nonExistentId = Str::uuid();

        // Make the request with a non-existent ID
        $response = $this->getJson("/api/tanah/public/{$nonExistentId}");

        // Assert we get a 404 response with the expected error message
        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data tanah tidak ditemukan'
            ]);
    }

    public function test_public_show_returns_403_for_unapproved_tanah()
    {
        // Create tanah records with statuses other than 'disetujui'
        $pendingTanah = Tanah::factory()->create(['status' => 'ditinjau']);
        $rejectedTanah = Tanah::factory()->create(['status' => 'ditolak']);

        // Test with pending tanah
        $response1 = $this->getJson("/api/tanah/public/{$pendingTanah->id_tanah}");
        $response1->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data tanah tidak tersedia untuk publik'
            ]);

        // Test with rejected tanah
        $response2 = $this->getJson("/api/tanah/public/{$rejectedTanah->id_tanah}");
        $response2->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data tanah tidak tersedia untuk publik'
            ]);
    }

    public function test_public_show_handles_exceptions_simple()
    {
        // Create a fake ID that will cause the database to throw an exception
        // This is a hacky approach but often works for simple cases
        $fakeId = 'not-a-valid-uuid';

        // Make the request with the invalid ID
        $response = $this->getJson("/api/tanah/public/{$fakeId}");

        // Assert we get a 500 response
        $response->assertStatus(500);
    }

    public function test_public_search_returns_validation_error_when_keyword_missing()
    {
        // Send request without keyword
        $response = $this->getJson('/api/tanah/search/public');

        // Assert validation error response
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                "status" => "error",
                "message" => "Validasi gagal"
            ])
            ->assertJsonValidationErrors(['keyword']);
    }

    public function test_public_search_returns_validation_error_when_keyword_too_short()
    {
        // Send request with too short keyword
        $response = $this->getJson('/api/tanah/search/public?keyword=ab');

        // Assert validation error response
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJson([
                "status" => "error",
                "message" => "Validasi gagal"
            ])
            ->assertJsonValidationErrors(['keyword']);
    }

    public function test_public_search_returns_matching_records_by_lokasi()
    {
        // Create approved tanah with matching lokasi
        $matchingTanah = Tanah::factory()->create([
            'lokasi' => 'Jalan Cendrawasih',
            'status' => 'disetujui'
        ]);

        // Create non-matching tanah
        Tanah::factory()->create([
            'lokasi' => 'Jalan Kenari',
            'status' => 'disetujui'
        ]);

        // Send search request
        $response = $this->getJson('/api/tanah/search/public?keyword=Cendra');

        // Assert response contains only matching record
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                "status" => "success",
                "message" => "Pencarian tanah berhasil"
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_tanah', (string)$matchingTanah->id_tanah);
    }

    public function test_public_search_handles_exception_simple()
    {
        // Keyword khusus untuk memicu exception di controller
        $keyword = 'force-exception';

        // Kirim request ke endpoint dengan keyword pemicu
        $response = $this->getJson("/api/tanah/search/public?keyword={$keyword}");

        // Assert bahwa kita menerima response 500
        $response->assertStatus(500);
    }

    private $validJenis = [
        'sawah',
        'kebun',
        'pekarangan',
        'ladang',
        'hutan'
    ];

    /** @test */
    public function it_returns_approved_lands_by_type()
    {
        // Buat data tanah yang disetujui dengan jenis tertentu
        $approvedLands = Tanah::factory()->count(3)->create([
            'jenis_tanah' => 'sawah',
            'status' => 'disetujui'
        ]);

        // Buat data tanah dengan status lain yang tidak seharusnya muncul
        Tanah::factory()->create(['jenis_tanah' => 'sawah', 'status' => 'ditinjau']);
        Tanah::factory()->create(['jenis_tanah' => 'sawah', 'status' => 'ditolak']);
        Tanah::factory()->create(['jenis_tanah' => 'kebun', 'status' => 'disetujui']);

        $response = $this->getJson('/api/tanah/jenis/sawah/public');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data tanah berdasarkan jenis berhasil diambil'
            ])
            ->assertJsonCount(3, 'data'); // Hanya 3 data sawah yang disetujui
    }

    /** @test */
    public function test_public_by_jenis_handles_exception_simple()
    {
        // Jenis tanah khusus untuk memicu exception di controller
        $jenisTanah = 'force-exception';

        // Kirim request ke endpoint dengan jenis tanah pemicu
        $response = $this->getJson("/api/tanah/jenis/{$jenisTanah}/public");

        // Assert bahwa kita menerima response 500
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server'
            ]);
    }

    /** @test */
    public function it_returns_approved_lands_by_pimpinan_name()
    {
        // Nama pimpinan yang digunakan untuk filter
        $pimpinanName = 'PimpinanJamaah';

        // Buat data tanah yang disetujui dengan nama pimpinan tertentu
        $approvedLands = Tanah::factory()->count(2)->create([
            'NamaPimpinanJamaah' => $pimpinanName,
            'status' => 'disetujui'
        ]);

        // Buat data tanah dengan status lain yang tidak seharusnya muncul
        Tanah::factory()->create(['NamaPimpinanJamaah' => $pimpinanName, 'status' => 'ditinjau']);
        Tanah::factory()->create(['NamaPimpinanJamaah' => $pimpinanName, 'status' => 'ditolak']);
        Tanah::factory()->create(['NamaPimpinanJamaah' => 'Pimpinan Lain', 'status' => 'disetujui']);

        // Kirim permintaan ke API
        $response = $this->getJson("/api/tanah/pimpinan/".urlencode($pimpinanName)."/public");

        // Memastikan bahwa status kode yang diterima adalah 200 OK
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data tanah berdasarkan pimpinan jamaah berhasil diambil'
            ])
            // Pastikan hanya 2 data yang sesuai dengan NamaPimpinanJamaah dan status 'disetujui'
            ->assertJsonCount(2, 'data')
            // Memastikan NamaPimpinanJamaah ada dalam data yang diterima
            ->assertJsonFragment(['NamaPimpinanJamaah' => $pimpinanName])
            // Memastikan data dengan NamaPimpinanJamaah 'Pimpinan Lain' tidak ada
            ->assertJsonMissing(['NamaPimpinanJamaah' => 'Pimpinan Lain']);
    }

    /** @test */
    public function it_handles_server_error_when_exception_occurs()
    {
        // Simulasikan exception
        $this->withoutExceptionHandling(); // Mematikan penanganan pengecualian oleh Laravel untuk melihat output asli
        $namaPimpinan = 'force-exception'; // Nama pimpinan yang menyebabkan exception

        // Menggunakan mock untuk memaksa exception dilempar di dalam controller
        // Simulasi error pada method publicByPimpinan
        $response = $this->getJson("/api/tanah/pimpinan/".urlencode($namaPimpinan)."/public");

        // Memastikan status kode yang diterima adalah HTTP 500 (Internal Server Error)
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            // Memastikan pesan error yang benar dikembalikan
            ->assertJson([
                "status" => "error",
                "message" => "Terjadi kesalahan server"
            ]);
    }

    public function test_store_handles_coordinates_correctly()
    {
        $user = User::factory()->create([
            'role_id' => '3594bece-a684-4287-b0a2-7429199772a3'
        ]);
        $this->actingAs($user);

        $response = $this->postJson('/api/tanah', [
            'NamaPimpinanJamaah' => 'Test Pimpinan',
            'NamaWakif' => 'Test Wakif',
            'lokasi' => 'Test Lokasi',
            'luasTanah' => '100',
            'latitude' => -6.2,
            'longitude' => 106.816666,
        ]);

        $response->assertStatus(201);

        // Cek latitude & longitude biasa
        $this->assertDatabaseHas('tanahs', [
            'latitude' => -6.2,
            'longitude' => 106.816666,
        ]);

        // ✅ Cek koordinat pakai raw query karena tipe kolomnya spatial
        $exists = DB::table('tanahs')
            ->whereRaw("ST_AsText(koordinat) = 'POINT(106.816666 -6.2)'")
            ->exists();

        $this->assertTrue($exists, 'Koordinat POINT tidak ditemukan di database.');
    }

    public function test_update_koordinat_successfully()
    {
        // Buat user dengan role non-pimpinan jamaah (langsung update, bukan approval)
        $user = User::factory()->create([
            'role_id' => '3594bece-a684-4287-b0a2-7429199772a3'
        ]);

        // Login sebagai user
        $this->actingAs($user);

        // Buat data tanah awal
        $tanah = Tanah::factory()->create([
            'latitude' => -6.1,
            'longitude' => 106.7,
        ]);

        // Set koordinat awal secara manual ke PostGIS
        DB::table('tanahs')->where('id_tanah', $tanah->id_tanah)->update([
            'koordinat' => DB::raw("ST_GeomFromText('POINT(106.7 -6.1)', 4326)")
        ]);

        // Kirim request update dengan koordinat baru
        $response = $this->putJson("/api/tanah/{$tanah->id_tanah}", [
            'latitude' => -6.2,
            'longitude' => 106.816666
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data tanah berhasil diperbarui.'
            ]);

        // Pastikan latitude dan longitude diupdate
        $this->assertDatabaseHas('tanahs', [
            'id_tanah' => $tanah->id_tanah,
            'latitude' => -6.2,
            'longitude' => 106.816666,
        ]);

        // Cek koordinat menggunakan ST_AsText
        $this->assertTrue(
            DB::table('tanahs')
                ->where('id_tanah', $tanah->id_tanah)
                ->whereRaw("ST_AsText(koordinat) = 'POINT(106.816666 -6.2)'")
                ->exists()
        );
    }



}
