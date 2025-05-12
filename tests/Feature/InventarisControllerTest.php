<?php

namespace Tests\Unit\Http\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Inventaris;
use App\Models\Fasilitas;
use App\Models\PemetaanFasilitas;
use App\Models\Tanah;
use App\Models\PemetaanTanah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Mockery;
use Illuminate\Support\Facades\Log;


class InventarisControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $fasilitas;
    protected $inventaris;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Authenticate as the test user
        $this->actingAs($this->user);

        // Create test tanah
        $tanah = Tanah::factory()->create(['user_id' => $this->user->id]);

        // Create test pemetaan tanah
        $pemetaanTanah = PemetaanTanah::factory()->create(['id_user' => $this->user->id]);

        // Create test pemetaan fasilitas
        $pemetaanFasilitas = PemetaanFasilitas::factory()->create(['id_user' => $this->user->id]);

        // Create test fasilitas
        $this->fasilitas = Fasilitas::factory()->create([
            'id_pemetaan_fasilitas' => $pemetaanFasilitas->id_pemetaan_fasilitas,
            'id_tanah' => $tanah->id_tanah
        ]);

        // Create test inventaris
        $this->inventaris = Inventaris::factory()->create([
            'id_fasilitas' => $this->fasilitas->id_fasilitas
        ]);
    }

    protected function mockActivityLog()
    {
        // Mock the ActivityLog model to prevent actual database operations
        $mock = Mockery::mock('alias:App\Models\ActivityLog');
        $mock->shouldReceive('create')->andReturn(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_list_all_inventaris()
    {
        $response = $this->getJson('/api/inventaris');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id_inventaris',
                    'id_fasilitas',
                    'nama_barang',
                    'satuan',
                    'jumlah',
                    'kondisi',
                    'created_at',
                    'updated_at',
                    'fasilitas' => [
                        'id_fasilitas',
                        'id_pemetaan_fasilitas',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_specific_inventaris()
    {
        $response = $this->getJson('/api/inventaris/' . $this->inventaris->id_inventaris);

        $response->assertStatus(200)
            ->assertJson([
                'id_inventaris' => $this->inventaris->id_inventaris,
                'nama_barang' => $this->inventaris->nama_barang
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_inventaris()
    {
        $nonExistentId = Str::uuid();
        $response = $this->getJson('/api/inventaris/' . $nonExistentId);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_list_inventaris_by_fasilitas()
    {
        $response = $this->getJson('/api/inventaris/fasilitas/' . $this->fasilitas->id_fasilitas);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    [
                        'id_fasilitas' => $this->fasilitas->id_fasilitas,
                        'id_inventaris' => $this->inventaris->id_inventaris
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_create_new_inventaris()
    {
        $data = [
            'id_fasilitas' => $this->fasilitas->id_fasilitas,
            'nama_barang' => 'Meja Kantor',
            'satuan' => 'Unit',
            'jumlah' => 5,
            'kondisi' => 'baik'
        ];

        $response = $this->postJson('/api/inventaris', $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Inventaris berhasil dibuat',
                'data' => [
                    'nama_barang' => 'Meja Kantor'
                ]
            ]);

        $this->assertDatabaseHas('inventaris', ['nama_barang' => 'Meja Kantor']);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_inventaris()
    {
        $response = $this->postJson('/api/inventaris', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'id_fasilitas',
                'nama_barang',
                'satuan',
                'jumlah',
                'kondisi'
            ]);
    }

    /** @test */
    public function it_validates_fasilitas_exists_when_creating_inventaris()
    {
        $nonExistentFasilitasId = Str::uuid();
        $data = [
            'id_fasilitas' => $nonExistentFasilitasId,
            'nama_barang' => 'Meja Kantor',
            'satuan' => 'Unit',
            'jumlah' => 5,
            'kondisi' => 'baik'
        ];

        $response = $this->postJson('/api/inventaris', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_fasilitas']);
    }

    /** @test */
    public function it_can_update_inventaris()
    {
        $data = [
            'nama_barang' => 'Meja Kantor Updated',
            'jumlah' => 10,
            'kondisi' => 'rusak_ringan'
        ];

        $response = $this->putJson('/api/inventaris/' . $this->inventaris->id_inventaris, $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Inventaris berhasil diperbarui',
                'data' => [
                    'nama_barang' => 'Meja Kantor Updated',
                    'jumlah' => 10,
                    'kondisi' => 'rusak_ringan'
                ]
            ]);

        $this->assertDatabaseHas('inventaris', [
            'id_inventaris' => $this->inventaris->id_inventaris,
            'nama_barang' => 'Meja Kantor Updated'
        ]);
    }

    /** @test */
    public function it_validates_fields_when_updating_inventaris()
    {
        $data = [
            'jumlah' => -1, // invalid
            'kondisi' => 'invalid_kondisi'
        ];

        $response = $this->putJson('/api/inventaris/' . $this->inventaris->id_inventaris, $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'jumlah',
                'kondisi'
            ]);
    }

    /** @test */
    public function it_can_delete_inventaris()
    {
        $response = $this->deleteJson('/api/inventaris/' . $this->inventaris->id_inventaris);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Inventaris berhasil dihapus'
            ]);

        $this->assertDatabaseMissing('inventaris', ['id_inventaris' => $this->inventaris->id_inventaris]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_inventaris()
    {
        $nonExistentId = Str::uuid();
        $response = $this->deleteJson('/api/inventaris/' . $nonExistentId);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_show_public_inventaris_by_pemetaan_fasilitas()
    {
        $response = $this->getJson('/api/inventaris/fasilitas/' . $this->fasilitas->pemetaanFasilitas->id_pemetaan_fasilitas . '/public/detail');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    [
                        'id_inventaris' => $this->inventaris->id_inventaris,
                        'id_fasilitas' => $this->fasilitas->id_fasilitas
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_returns_error_for_nonexistent_pemetaan_fasilitas()
    {
        $nonExistentId = Str::uuid();
        $response = $this->getJson('/api/inventaris/fasilitas/' . $nonExistentId . '/public/detail');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Fasilitas tidak ditemukan'
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_protected_routes()
    {
        Auth::logout();

        $routes = [
            ['method' => 'get', 'url' => '/api/inventaris'],
            ['method' => 'post', 'url' => '/api/inventaris'],
            ['method' => 'get', 'url' => '/api/inventaris/' . $this->inventaris->id_inventaris],
            ['method' => 'put', 'url' => '/api/inventaris/' . $this->inventaris->id_inventaris],
            ['method' => 'delete', 'url' => '/api/inventaris/' . $this->inventaris->id_inventaris],
            ['method' => 'get', 'url' => '/api/inventaris/fasilitas/' . $this->fasilitas->id_fasilitas],
        ];

        foreach ($routes as $route) {
            $response = $this->{$route['method'] . 'Json'}($route['url']);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function it_handles_exceptions_during_inventaris_creation()
    {
        // Mock the request data with testing flag
        $requestData = [
            'id_fasilitas' => $this->fasilitas->id_fasilitas,
            'nama_barang' => 'Meja Kantor',
            'satuan' => 'Unit',
            'jumlah' => 5,
            'kondisi' => 'baik',
            'force_db_error' => true // This triggers the exception
        ];

        // Mock the logger to verify the error is logged
        Log::shouldReceive('error')
            ->once()
            ->with('Error creating inventaris: Database error for testing');

        // Make the request
        $response = $this->postJson('/api/inventaris', $requestData);

        // Assert the response
        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => 'Database error for testing'
            ]);
    }

    /** @test */
    public function it_handles_exceptions_during_inventaris_update()
    {
        // Create test inventaris data
        $inventaris = Inventaris::factory()->create([
            'id_fasilitas' => $this->fasilitas->id_fasilitas,
            'nama_barang' => 'Meja Lama',
            'jumlah' => 2
        ]);

        // Mock the request data with testing flag
        $updateData = [
            'nama_barang' => 'Meja Baru',
            'jumlah' => 5,
            'force_db_error' => true // This triggers the exception
        ];

        // Mock the logger to verify the error is logged
        Log::shouldReceive('error')
            ->once()
            ->with('Error updating inventaris: Database error for testing');

        // Make the request
        $response = $this->putJson("/api/inventaris/{$inventaris->id_inventaris}", $updateData);

        // Assert the response
        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data',
                'error' => 'Database error for testing'
            ]);
    }

    /** @test */
    /** @test */
    public function it_includes_specific_items_in_public_inventaris_list()
    {
        // Create our test items
        $testItems = [
            Inventaris::factory()->create([
                'id_fasilitas' => $this->fasilitas->id_fasilitas,
                'nama_barang' => 'Meja Kantor',
                'satuan' => 'Unit',
                'jumlah' => 5,
                'kondisi' => 'baik'
            ]),
            Inventaris::factory()->create([
                'id_fasilitas' => $this->fasilitas->id_fasilitas,
                'nama_barang' => 'Kursi',
                'satuan' => 'Buah',
                'jumlah' => 10,
                'kondisi' => 'rusak_ringan'
            ])
        ];

        $response = $this->getJson("/api/inventaris/fasilitas/{$this->fasilitas->id_fasilitas}/public");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id_fasilitas' => $this->fasilitas->id_fasilitas,
                'nama_barang' => 'Meja Kantor',
                'satuan' => 'Unit',
                'jumlah' => 5,
                'kondisi' => 'baik'
            ])
            ->assertJsonFragment([
                'id_fasilitas' => $this->fasilitas->id_fasilitas,
                'nama_barang' => 'Kursi',
                'satuan' => 'Buah',
                'jumlah' => 10,
                'kondisi' => 'rusak_ringan'
            ])
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id_inventaris',
                        'id_fasilitas',
                        'nama_barang',
                        'kode_barang',
                        'satuan',
                        'jumlah',
                        'kondisi',
                        'waktu_perolehan'
                    ]
                ]
            ]);
    }
}
