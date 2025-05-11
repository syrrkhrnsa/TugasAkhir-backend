<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PemetaanTanah;
use App\Models\PemetaanFasilitas;
use App\Models\Tanah;
use App\Models\Fasilitas;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;


class PemetaanTanahControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->tanah = Tanah::factory()->create();
    }

    // Test public index
    public function test_public_index_returns_success()
    {
        $response = $this->getJson('/api/pemetaan/public');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_index_all_returns_success()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/pemetaan/tanah');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }
    public function test_public_show_returns_pemetaan_tanah_with_relations()
    {
        // Setup: Buat pengguna untuk autentikasi dalam test
        $user = User::factory()->create();

        // Setup: Buat data pemetaan tanah
        $pemetaan = PemetaanTanah::factory()
            ->has(PemetaanFasilitas::factory()->count(2), 'fasilitas')
            ->create([
                'nama_pemetaan' => 'Pemetaan Test',
                'keterangan' => 'Ini adalah keterangan test',
                'jenis_geometri' => 'POLYGON',
                'luas_tanah' => 500.25,
            ]);

        dd('Pemetaan created', $pemetaan);

        // Pastikan user_id ada dalam log aktivitas
        ActivityLog::factory()->create([
            'user_id' => $user->id, // Tentukan user_id yang valid
            'model_type' => 'App\Models\PemetaanTanah',
            'model_id' => $pemetaan->id_pemetaan_tanah,
            'action' => 'create',
            'changes' => json_encode([
                'id_tanah' => $pemetaan->id_tanah,
                'nama_pemetaan' => 'Pemetaan Test',
                'keterangan' => 'Ini adalah keterangan test',
                'jenis_geometri' => 'POLYGON',
                'luas_tanah' => 500.25,
            ]),
        ]);

        // Simulasikan pengguna yang sedang login
        $this->actingAs($user);

        // Action: Kirim request untuk mendapatkan data pemetaan tanah
        $response = $this->getJson("/api/pemetaan/public/{$pemetaan->id_pemetaan_tanah}");

        // Assertion: Verifikasi response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => null,
            ]);
    }



    public function test_public_show_returns_404_for_nonexistent_pemetaan()
    {
        $nonExistentId = Str::uuid();

        $response = $this->getJson("/api/pemetaan/public/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Pemetaan tanah tidak ditemukan'
            ]);
    }

    // Test show detail
    public function test_show_detail_returns_pemetaan_tanah_with_relations()
    {
        $this->actingAs($this->user, 'sanctum');
        $pemetaan = PemetaanTanah::factory()->create();

        $response = $this->getJson("/api/pemetaan/tanah-detail/{$pemetaan->id_pemetaan_tanah}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_pemetaan_tanah' => $pemetaan->id_pemetaan_tanah
                ]
            ]);
    }

    // Test public by tanah
    public function test_public_by_tanah_returns_pemetaan_for_specific_tanah()
    {
        $pemetaan = PemetaanTanah::factory()->create(['id_tanah' => $this->tanah->id_tanah]);
        PemetaanTanah::factory()->create(); // Other pemetaan

        $response = $this->getJson("/api/tanah/{$this->tanah->id_tanah}/pemetaan/public");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_tanah', $this->tanah->id_tanah);
    }

    // Test get user pemetaan tanah
    public function test_get_user_pemetaan_tanah_returns_pemetaan_for_specific_user()
    {
        $this->actingAs($this->user, 'sanctum');
        $pemetaan = PemetaanTanah::factory()->create(['id_user' => $this->user->id]);
        PemetaanTanah::factory()->create(); // Other pemetaan

        $response = $this->getJson("/api/pemetaan/user/pemetaan-tanah/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_user', $this->user->id);
    }

    public function test_get_user_pemetaan_tanah_returns_error_for_missing_user_id()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson("/api/pemetaan/user/pemetaan-tanah/");

        $response->assertStatus(404);
    }

    // Test get user pemetaan tanah detail
    public function test_get_user_pemetaan_tanah_detail_returns_specific_pemetaan()
    {
        $this->actingAs($this->user, 'sanctum');
        $pemetaan = PemetaanTanah::factory()->create(['id_user' => $this->user->id]);

        $response = $this->getJson("/api/pemetaan/user/pemetaan-tanah/{$this->user->id}/{$pemetaan->id_pemetaan_tanah}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_pemetaan_tanah' => $pemetaan->id_pemetaan_tanah,
                    'id_user' => $this->user->id
                ]
            ]);
    }

    public function test_get_user_pemetaan_tanah_detail_returns_404_for_wrong_user()
    {
        $this->actingAs($this->user, 'sanctum');
        $otherUser = User::factory()->create();
        $pemetaan = PemetaanTanah::factory()->create(['id_user' => $otherUser->id]);

        $response = $this->getJson("/api/pemetaan/user/pemetaan-tanah/{$this->user->id}/{$pemetaan->id_pemetaan_tanah}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data pemetaan tanah tidak ditemukan atau tidak memiliki akses'
            ]);
    }

    // Test index by tanah ID
    public function test_index_returns_pemetaan_for_specific_tanah()
    {
        $this->actingAs($this->user, 'sanctum');
        $pemetaan = PemetaanTanah::factory()->create(['id_tanah' => $this->tanah->id_tanah]);
        PemetaanTanah::factory()->create(); // Other pemetaan

        $response = $this->getJson("/api/pemetaan/tanah/{$this->tanah->id_tanah}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_tanah', $this->tanah->id_tanah);
    }

    // Test store
    public function test_store_creates_new_pemetaan_tanah()
    {
        $this->actingAs($this->user, 'sanctum');

        $data = [
            'nama_pemetaan' => 'Test Pemetaan',
            'jenis_geometri' => 'POLYGON',
            'geometri' => json_encode([
                'type' => 'Polygon',
                'coordinates' => [[
                    [106.827, -6.175],
                    [106.828, -6.175],
                    [106.828, -6.176],
                    [106.827, -6.176],
                    [106.827, -6.175]
                ]]
            ]),
            'keterangan' => 'Test keterangan'
        ];

        $response = $this->postJson("/api/pemetaan/tanah/{$this->tanah->id_tanah}", $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pemetaan tanah berhasil dibuat'
            ]);

        $this->assertDatabaseHas('pemetaan_tanah', [
            'nama_pemetaan' => 'Test Pemetaan',
            'id_tanah' => $this->tanah->id_tanah,
            'id_user' => $this->user->id
        ]);
    }

    public function test_store_returns_validation_errors()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson("/api/pemetaan/tanah/{$this->tanah->id_tanah}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'nama_pemetaan',
                'jenis_geometri',
                'geometri'
            ]);
    }

    // Test show
    public function test_show_returns_pemetaan_with_fasilitas()
    {
        $this->actingAs($this->user, 'sanctum');
        $pemetaan = PemetaanTanah::factory()->create();

        $response = $this->getJson("/api/pemetaan/tanah-detail/{$pemetaan->id_pemetaan_tanah}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_pemetaan_tanah' => $pemetaan->id_pemetaan_tanah
                ]
            ]);
    }

    // Test update
    public function test_update_modifies_existing_pemetaan()
    {
        $this->actingAs($this->user, 'sanctum');
        $pemetaan = PemetaanTanah::factory()->create(['id_user' => $this->user->id]);

        $data = [
            'nama_pemetaan' => 'Updated Name',
            'keterangan' => 'Updated Keterangan'
        ];

        $response = $this->putJson("/api/pemetaan/tanah/{$pemetaan->id_pemetaan_tanah}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pemetaan tanah berhasil diperbarui'
            ]);

        $this->assertDatabaseHas('pemetaan_tanah', [
            'id_pemetaan_tanah' => $pemetaan->id_pemetaan_tanah,
            'nama_pemetaan' => 'Updated Name'
        ]);
    }

    public function test_update_returns_validation_errors()
    {
        $this->actingAs($this->user, 'sanctum');
        $pemetaan = PemetaanTanah::factory()->create(['id_user' => $this->user->id]);

        $data = [
            'jenis_geometri' => 'INVALID_TYPE'
        ];

        $response = $this->putJson("/api/pemetaan/tanah/{$pemetaan->id_pemetaan_tanah}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['jenis_geometri']);
    }

    // Test destroy
    public function test_destroy_deletes_pemetaan()
    {
        $this->actingAs($this->user, 'sanctum');
        $pemetaan = PemetaanTanah::factory()->create(['id_user' => $this->user->id]);

        $response = $this->deleteJson("/api/pemetaan/tanah/{$pemetaan->id_pemetaan_tanah}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pemetaan tanah berhasil dihapus'
            ]);

        $this->assertDatabaseMissing('pemetaan_tanah', [
            'id_pemetaan_tanah' => $pemetaan->id_pemetaan_tanah
        ]);
    }

    public function test_destroy_returns_404_for_nonexistent_pemetaan()
    {
        $this->actingAs($this->user, 'sanctum');
        $nonExistentId = Str::uuid();

        $response = $this->deleteJson("/api/pemetaan/tanah/{$nonExistentId}");

        $response->assertStatus(404);
    }
}
