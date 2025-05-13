<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Fasilitas;
use App\Models\PemetaanFasilitas;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FasilitasControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');
    }

    public function test_index_returns_all_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $fasilitas = Fasilitas::factory()->count(3)->create();

        $response = $this->getJson('/api/fasilitas');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id_fasilitas',
                        'id_pemetaan_fasilitas',
                        'id_tanah',
                        'catatan',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_index_returns_empty_array_when_no_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/fasilitas');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => []
            ]);
    }

    public function test_public_index_returns_fasilitas_with_relationships()
    {
        // Create test data with relationships
        $fasilitas = Fasilitas::factory()
            ->has(PemetaanFasilitas::factory())
            ->has(Tanah::factory())
            ->has(FilePendukungFasilitas::factory()->count(2))
            ->count(3)
            ->create();

        $response = $this->getJson('/api/fasilitas/detail/public');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id_fasilitas',
                        'id_pemetaan_fasilitas',
                        'id_tanah',
                        'catatan',
                        'created_at',
                        'updated_at',
                        'pemetaan_fasilitas',
                        'tanah',
                        'file_pendukung' => [
                            '*' => [
                                'id_file_pendukung',
                                'id_fasilitas',
                                'nama_file',
                                'path_file',
                                'jenis_file',
                                'created_at',
                                'updated_at'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_public_index_returns_empty_when_no_fasilitas()
    {
        $response = $this->getJson('/api/fasilitas/detail/public');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => []
            ]);
    }

    public function test_public_show_returns_fasilitas_with_all_relationships()
    {
        // Create test data with all relationships
        $fasilitas = Fasilitas::factory()
            ->has(PemetaanFasilitas::factory())
            ->has(Tanah::factory())
            ->has(FilePendukungFasilitas::factory()->count(2))
            ->has(Inventaris::factory()->count(3))
            ->create();

        $response = $this->getJson("/api/fasilitas/detail/public/{$fasilitas->id_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_fasilitas' => $fasilitas->id_fasilitas,
                    'pemetaan_fasilitas' => [
                        'id_pemetaan_fasilitas' => $fasilitas->pemetaanFasilitas->id_pemetaan_fasilitas
                    ],
                    'tanah' => [
                        'id_tanah' => $fasilitas->tanah->id_tanah
                    ],
                    'inventaris' => [
                        '*' => [
                            'id_inventaris',
                            'id_fasilitas',
                            'nama_barang',
                            'satuan',
                            'jumlah',
                            'kondisi'
                        ]
                    ],
                    'file_pendukung' => [
                        '*' => [
                            'id_file_pendukung',
                            'id_fasilitas',
                            'nama_file',
                            'jenis_file'
                        ]
                    ]
                ]
            ]);
    }

    public function test_public_show_returns_404_for_invalid_id()
    {
        $invalidId = 'non-existent-id';

        $response = $this->getJson("/api/fasilitas/detail/public/{$invalidId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Fasilitas tidak ditemukan'
            ]);
    }

    public function test_index_handles_database_errors()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Mock database error
        $mock = $this->mock(Fasilitas::class);
        $mock->shouldReceive('with')->andThrow(new \Exception('Database error'));

        $response = $this->getJson('/api/fasilitas');

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data'
            ]);
    }

    public function test_public_show_handles_database_errors()
    {
        $fasilitas = Fasilitas::factory()->create();

        // Mock database error
        $mock = $this->mock(Fasilitas::class);
        $mock->shouldReceive('with')->andThrow(new \Exception('Database error'));

        $response = $this->getJson("/api/fasilitas/detail/public/{$fasilitas->id_fasilitas}");

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data fasilitas'
            ]);
    }

    public function test_store_creates_new_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $pemetaanFasilitas = PemetaanFasilitas::factory()->create();
        $tanah = Tanah::factory()->create();

        $data = [
            'id_pemetaan_fasilitas' => $pemetaanFasilitas->id_pemetaan_fasilitas,
            'id_tanah' => $tanah->id_tanah,
            'catatan' => 'Test catatan'
        ];

        $response = $this->postJson('/api/fasilitas', $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Fasilitas berhasil dibuat'
            ]);

        $this->assertDatabaseHas('fasilitas', [
            'id_pemetaan_fasilitas' => $pemetaanFasilitas->id_pemetaan_fasilitas,
            'id_tanah' => $tanah->id_tanah,
            'catatan' => 'Test catatan'
        ]);
    }

    public function test_store_returns_error_when_fasilitas_already_exists()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $pemetaanFasilitas = PemetaanFasilitas::factory()->create();
        $tanah = Tanah::factory()->create();

        // Create existing fasilitas
        Fasilitas::factory()->create([
            'id_pemetaan_fasilitas' => $pemetaanFasilitas->id_pemetaan_fasilitas
        ]);

        $data = [
            'id_pemetaan_fasilitas' => $pemetaanFasilitas->id_pemetaan_fasilitas,
            'id_tanah' => $tanah->id_tanah,
            'catatan' => 'Test catatan'
        ];

        $response = $this->postJson('/api/fasilitas', $data);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Fasilitas untuk pemetaan ini sudah ada'
            ]);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/fasilitas', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'id_pemetaan_fasilitas',
                'id_tanah'
            ]);
    }

    public function test_show_returns_fasilitas_details()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $fasilitas = Fasilitas::factory()->create();

        $response = $this->getJson("/api/fasilitas/{$fasilitas->id_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_fasilitas' => $fasilitas->id_fasilitas
                ]
            ]);
    }

    public function test_show_returns_404_for_nonexistent_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $nonExistentId = Str::uuid();

        $response = $this->getJson("/api/fasilitas/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Fasilitas tidak ditemukan'
            ]);
    }

    public function test_update_modifies_existing_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $fasilitas = Fasilitas::factory()->create();
        $newTanah = Tanah::factory()->create();

        $updateData = [
            'id_tanah' => $newTanah->id_tanah,
            'catatan' => 'Updated catatan'
        ];

        $response = $this->putJson("/api/fasilitas/{$fasilitas->id_fasilitas}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Fasilitas berhasil diperbarui'
            ]);

        $this->assertDatabaseHas('fasilitas', [
            'id_fasilitas' => $fasilitas->id_fasilitas,
            'id_tanah' => $newTanah->id_tanah,
            'catatan' => 'Updated catatan'
        ]);
    }

    public function test_update_can_find_fasilitas_by_pemetaan_id()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $fasilitas = Fasilitas::factory()->create();
        $newTanah = Tanah::factory()->create();

        $updateData = [
            'id_tanah' => $newTanah->id_tanah,
            'catatan' => 'Updated catatan'
        ];

        // Use pemetaan ID instead of fasilitas ID
        $response = $this->putJson("/api/fasilitas/{$fasilitas->id_pemetaan_fasilitas}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Fasilitas berhasil diperbarui'
            ]);

        $this->assertDatabaseHas('fasilitas', [
            'id_fasilitas' => $fasilitas->id_fasilitas,
            'id_tanah' => $newTanah->id_tanah,
            'catatan' => 'Updated catatan'
        ]);
    }

    public function test_update_returns_404_for_nonexistent_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $nonExistentId = Str::uuid();
        $updateData = ['catatan' => 'Updated'];

        $response = $this->putJson("/api/fasilitas/{$nonExistentId}", $updateData);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Fasilitas tidak ditemukan'
            ]);
    }

    public function test_destroy_deletes_fasilitas_and_related_files()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $fasilitas = Fasilitas::factory()->create();

        // Mock file deletion
        Storage::shouldReceive('disk->delete')->once();

        $response = $this->deleteJson("/api/fasilitas/{$fasilitas->id_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Fasilitas dan semua file terkait berhasil dihapus'
            ]);

        $this->assertDatabaseMissing('fasilitas', [
            'id_fasilitas' => $fasilitas->id_fasilitas
        ]);
    }

    public function test_destroy_returns_404_for_nonexistent_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $nonExistentId = Str::uuid();

        $response = $this->deleteJson("/api/fasilitas/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal menghapus fasilitas'
            ]);
    }

    public function test_show_by_pemetaan_fasilitas_returns_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $fasilitas = Fasilitas::factory()->create();

        $response = $this->getJson("/api/fasilitas/pemetaan/{$fasilitas->id_pemetaan_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_pemetaan_fasilitas' => $fasilitas->id_pemetaan_fasilitas
                ]
            ]);
    }

    public function test_show_by_pemetaan_fasilitas_returns_404_when_not_found()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $nonExistentId = Str::uuid();

        $response = $this->getJson("/api/fasilitas/pemetaan/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Fasilitas tidak ditemukan'
            ]);
    }

    public function test_find_by_pemetaan_returns_fasilitas()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $fasilitas = Fasilitas::factory()->create();

        $response = $this->getJson("/api/fasilitas/by-pemetaan/{$fasilitas->id_pemetaan_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_pemetaan_fasilitas' => $fasilitas->id_pemetaan_fasilitas
                ]
            ]);
    }

    public function test_find_by_pemetaan_returns_404_when_not_found()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $nonExistentId = Str::uuid();

        $response = $this->getJson("/api/fasilitas/by-pemetaan/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Fasilitas tidak ditemukan'
            ]);
    }

    public function test_public_index_returns_all_fasilitas()
    {
        $fasilitas = Fasilitas::factory()->count(3)->create();

        $response = $this->getJson('/api/fasilitas/detail/public');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id_fasilitas',
                        'id_pemetaan_fasilitas',
                        'id_tanah',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_public_show_returns_fasilitas_details()
    {
        $fasilitas = Fasilitas::factory()->create();

        $response = $this->getJson("/api/fasilitas/detail/public/{$fasilitas->id_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_fasilitas' => $fasilitas->id_fasilitas
                ]
            ]);
    }

    public function test_public_show_returns_404_for_nonexistent_fasilitas()
    {
        $nonExistentId = Str::uuid();

        $response = $this->getJson("/api/fasilitas/detail/public/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Fasilitas tidak ditemukan'
            ]);
    }

    public function test_store_handles_database_errors()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $pemetaanFasilitas = PemetaanFasilitas::factory()->create();
        $tanah = Tanah::factory()->create();

        // Mock database error
        $mock = $this->mock(Fasilitas::class);
        $mock->shouldReceive('where')->andThrow(new \Exception('Database error'));

        $data = [
            'id_pemetaan_fasilitas' => $pemetaanFasilitas->id_pemetaan_fasilitas,
            'id_tanah' => $tanah->id_tanah,
            'catatan' => 'Test catatan'
        ];

        $response = $this->postJson('/api/fasilitas', $data);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data'
            ]);
    }

    public function test_update_handles_database_errors()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $fasilitas = Fasilitas::factory()->create();

        // Mock database error
        $mock = $this->mock(Fasilitas::class);
        $mock->shouldReceive('where')->andThrow(new \Exception('Database error'));

        $updateData = ['catatan' => 'Updated'];

        $response = $this->putJson("/api/fasilitas/{$fasilitas->id_fasilitas}", $updateData);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal memperbarui fasilitas'
            ]);
    }
}
