<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\PemetaanTanah;
use App\Models\PemetaanFasilitas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Illuminate\Support\Facades\Log;

class PemetaanFasilitasControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_pemetaan_fasilitas_publicly()
    {
        PemetaanFasilitas::factory()->count(3)->create();

        $response = $this->getJson('/api/fasilitas/public');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id_pemetaan_fasilitas',
                        'id_pemetaan_tanah',
                        'jenis_fasilitas',
                        'nama_fasilitas',
                        'pemetaan_tanah'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_get_all_pemetaan_fasilitas_for_authenticated_users()
    {
        PemetaanFasilitas::factory()->count(3)->create();

        $response = $this->getJson('/api/pemetaan/fasilitas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id_pemetaan_fasilitas',
                        'id_pemetaan_tanah',
                        'jenis_fasilitas',
                        'nama_fasilitas'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_show_pemetaan_fasilitas_detail_publicly()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $response = $this->getJson("/api/fasilitas/public/{$fasilitas->id_pemetaan_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_pemetaan_fasilitas' => $fasilitas->id_pemetaan_fasilitas,
                    'nama_fasilitas' => $fasilitas->nama_fasilitas
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_when_pemetaan_fasilitas_not_found_publicly()
    {
        $nonExistentId = \Illuminate\Support\Str::uuid();

        $response = $this->getJson("/api/fasilitas/public/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Pemetaan fasilitas tidak ditemukan'
            ]);
    }

    /** @test */
    public function it_can_show_pemetaan_fasilitas_detail_for_authenticated_users()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $response = $this->getJson("/api/pemetaan/fasilitas-detail/{$fasilitas->id_pemetaan_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_pemetaan_fasilitas' => $fasilitas->id_pemetaan_fasilitas,
                    'nama_fasilitas' => $fasilitas->nama_fasilitas
                ]
            ]);
    }

    /** @test */
    public function it_can_get_fasilitas_by_pemetaan_tanah_id_publicly()
    {
        $pemetaanTanah = PemetaanTanah::factory()->create();
        PemetaanFasilitas::factory()->count(2)->create(['id_pemetaan_tanah' => $pemetaanTanah->id_pemetaan_tanah]);

        $response = $this->getJson("/api/pemetaan/{$pemetaanTanah->id_pemetaan_tanah}/fasilitas/public");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_get_fasilitas_by_jenis_publicly()
    {
        PemetaanFasilitas::factory()->create(['jenis_fasilitas' => 'Bergerak']);
        PemetaanFasilitas::factory()->create(['jenis_fasilitas' => 'Tidak Bergerak']);

        $response = $this->getJson('/api/fasilitas/jenis/Bergerak/public');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['jenis_fasilitas' => 'Bergerak']);
    }

    /** @test */
    public function it_can_get_user_pemetaan_fasilitas()
    {
        $user = User::factory()->create();
        PemetaanFasilitas::factory()->count(2)->create(['id_user' => $user->id]);

        $response = $this->getJson("/api/pemetaan/user/pemetaan-fasilitas/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_requires_user_id_for_getting_user_pemetaan_fasilitas()
    {
        $response = $this->getJson('/api/pemetaan/user/pemetaan-fasilitas/');

        $response->assertStatus(404); // Route not found
    }

    /** @test */
    public function it_can_get_user_pemetaan_fasilitas_detail()
    {
        $user = User::factory()->create();
        $fasilitas = PemetaanFasilitas::factory()->create(['id_user' => $user->id]);

        $response = $this->getJson("/api/pemetaan/user/pemetaan-fasilitas/{$user->id}/{$fasilitas->id_pemetaan_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id_pemetaan_fasilitas' => $fasilitas->id_pemetaan_fasilitas
                ]
            ]);
    }

    /** @test */
    public function it_returns_error_when_user_pemetaan_fasilitas_not_found()
    {
        $user = User::factory()->create();
        $nonExistentId = \Illuminate\Support\Str::uuid();

        $response = $this->getJson("/api/pemetaan/user/pemetaan-fasilitas/{$user->id}/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data pemetaan fasilitas tidak ditemukan atau tidak memiliki akses'
            ]);
    }

    /** @test */
    public function it_can_get_fasilitas_by_pemetaan_tanah_id()
    {
        $pemetaanTanah = PemetaanTanah::factory()->create();
        PemetaanFasilitas::factory()->count(2)->create(['id_pemetaan_tanah' => $pemetaanTanah->id_pemetaan_tanah]);

        $response = $this->getJson("/api/pemetaan/fasilitas/{$pemetaanTanah->id_pemetaan_tanah}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_store_new_pemetaan_fasilitas()
    {
        $pemetaanTanah = PemetaanTanah::factory()->create();

        $data = [
            'jenis_fasilitas' => 'Bergerak',
            'kategori_fasilitas' => 'Test Kategori',
            'nama_fasilitas' => 'Test Fasilitas',
            'jenis_geometri' => 'POINT',
            'geometri' => json_encode([
                'type' => 'Point',
                'coordinates' => [106.0, -6.0]
            ]),
            'keterangan' => 'Test keterangan'
        ];

        $response = $this->postJson("/api/pemetaan/fasilitas/{$pemetaanTanah->id_pemetaan_tanah}", $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pemetaan fasilitas berhasil dibuat'
            ]);

        $this->assertDatabaseHas('pemetaan_fasilitas', [
            'nama_fasilitas' => 'Test Fasilitas',
            'id_pemetaan_tanah' => $pemetaanTanah->id_pemetaan_tanah
        ]);
    }

    /** @test */
    public function it_validates_store_request()
    {
        $pemetaanTanah = PemetaanTanah::factory()->create();

        $response = $this->postJson("/api/pemetaan/fasilitas/{$pemetaanTanah->id_pemetaan_tanah}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'jenis_fasilitas',
                'kategori_fasilitas',
                'nama_fasilitas',
                'jenis_geometri',
                'geometri'
            ]);
    }

    /** @test */
    public function it_can_update_pemetaan_fasilitas()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $data = [
            'nama_fasilitas' => 'Updated Fasilitas',
            'keterangan' => 'Updated keterangan'
        ];

        $response = $this->putJson("/api/pemetaan/fasilitas/{$fasilitas->id_pemetaan_fasilitas}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pemetaan fasilitas berhasil diperbarui'
            ]);

        $this->assertDatabaseHas('pemetaan_fasilitas', [
            'id_pemetaan_fasilitas' => $fasilitas->id_pemetaan_fasilitas,
            'nama_fasilitas' => 'Updated Fasilitas'
        ]);
    }

    /** @test */
    public function it_can_update_pemetaan_fasilitas_with_geometry()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $data = [
            'jenis_geometri' => 'POINT',
            'geometri' => json_encode([
                'type' => 'Point',
                'coordinates' => [106.1, -6.1]
            ])
        ];

        $response = $this->putJson("/api/pemetaan/fasilitas/{$fasilitas->id_pemetaan_fasilitas}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pemetaan fasilitas berhasil diperbarui'
            ]);
    }

    /** @test */
    public function it_validates_update_request()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $invalidData = [
            'jenis_fasilitas' => 'Invalid',
            'jenis_geometri' => 'Invalid'
        ];

        $response = $this->putJson("/api/pemetaan/fasilitas/{$fasilitas->id_pemetaan_fasilitas}", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'jenis_fasilitas',
                'jenis_geometri'
            ]);
    }

    /** @test */
    public function it_returns_404_when_updating_nonexistent_pemetaan_fasilitas()
    {
        $nonExistentId = \Illuminate\Support\Str::uuid();

        $response = $this->putJson("/api/pemetaan/fasilitas/{$nonExistentId}", [
            'nama_fasilitas' => 'Test'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Pemetaan fasilitas tidak ditemukan'
            ]);
    }

    /** @test */
    public function it_can_delete_pemetaan_fasilitas()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $response = $this->deleteJson("/api/pemetaan/fasilitas/{$fasilitas->id_pemetaan_fasilitas}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pemetaan fasilitas berhasil dihapus'
            ]);

        $this->assertDatabaseMissing('pemetaan_fasilitas', [
            'id_pemetaan_fasilitas' => $fasilitas->id_pemetaan_fasilitas
        ]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_pemetaan_fasilitas()
    {
        $nonExistentId = \Illuminate\Support\Str::uuid();

        $response = $this->deleteJson("/api/pemetaan/fasilitas/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Pemetaan fasilitas tidak ditemukan'
            ]);
    }

    /** @test */
    public function it_handles_database_errors_during_store()
    {
        $pemetaanTanah = PemetaanTanah::factory()->create();

        // Mock database error
        DB::shouldReceive('beginTransaction')->once()->andReturnNull();
        DB::shouldReceive('rollBack')->once()->andReturnNull();

        $data = [
            'jenis_fasilitas' => 'Bergerak',
            'kategori_fasilitas' => 'Test Kategori',
            'nama_fasilitas' => 'Test Fasilitas',
            'jenis_geometri' => 'POINT',
            'geometri' => json_encode([
                'type' => 'Point',
                'coordinates' => [106.0, -6.0]
            ]),
            'keterangan' => 'Test keterangan'
        ];

        $response = $this->postJson("/api/pemetaan/fasilitas/{$pemetaanTanah->id_pemetaan_tanah}", $data);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal membuat pemetaan fasilitas'
            ]);
    }

    /** @test */
    public function it_handles_general_errors_during_update()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        // Valid JSON tapi tidak sesuai dengan jenis_geometri 'POINT' (jadi error di geojsonToWkt)
        $invalidGeojson = json_encode([
            'type' => 'Polygon', // mismatch with jenis_geometri = POINT
            'coordinates' => [[[106.0, -6.0], [106.1, -6.0], [106.0, -6.1], [106.0, -6.0]]]
        ]);

        $response = $this->putJson("/api/pemetaan/fasilitas/{$fasilitas->id_pemetaan_fasilitas}", [
            'jenis_geometri' => 'POINT', // tapi geojson-nya Polygon
            'geometri' => $invalidGeojson,
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal memperbarui pemetaan fasilitas',
            ]);
    }



    public function it_handles_general_errors_during_destroy()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        // Mock partial model
        $mock = $this->partialMock(PemetaanFasilitas::class, function ($mock) {
            $mock->shouldReceive('delete')->andThrow(new \Exception('Test Error'));
        });

        // Force findOrFail to return our mock
        PemetaanFasilitas::shouldReceive('findOrFail')->andReturn($mock);

        $response = $this->deleteJson("/api/pemetaan/fasilitas/{$fasilitas->id_pemetaan_fasilitas}");

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal menghapus pemetaan fasilitas'
            ]);
    }

    /** @test */
    public function it_converts_linestring_geojson_to_wkt()
    {
        $controller = new \App\Http\Controllers\PemetaanFasilitasController();

        $geojson = [
            'type' => 'LineString',
            'coordinates' => [[1,2], [3,4], [5,6]]
        ];

        $result = $this->invokePrivateMethod($controller, 'geojsonToWkt', [$geojson, 'LINESTRING']);

        $this->assertEquals('LINESTRING(1 2, 3 4, 5 6)', $result);
    }

    /** @test */
    public function it_converts_polygon_geojson_to_wkt()
    {
        $controller = new \App\Http\Controllers\PemetaanFasilitasController();

        $geojson = [
            'type' => 'Polygon',
            'coordinates' => [[[1,2], [3,4], [5,6], [1,2]]]
        ];

        $result = $this->invokePrivateMethod($controller, 'geojsonToWkt', [$geojson, 'POLYGON']);

        $this->assertEquals('POLYGON((1 2, 3 4, 5 6, 1 2))', $result);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_geometry_type()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jenis geometri UNKNOWN belum didukung');

        $controller = new \App\Http\Controllers\PemetaanFasilitasController();
        $this->invokePrivateMethod($controller, 'geojsonToWkt', [[], 'UNKNOWN']);
    }

// Helper method to invoke private methods
    protected function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /** @test */
    public function it_handles_pemetaan_tanah_not_found_during_store()
    {
        $nonExistentId = \Illuminate\Support\Str::uuid();

        $data = [
            'jenis_fasilitas' => 'Bergerak',
            'kategori_fasilitas' => 'Test Kategori',
            'nama_fasilitas' => 'Test Fasilitas',
            'jenis_geometri' => 'POINT',
            'geometri' => json_encode(['type' => 'Point', 'coordinates' => [106.0, -6.0]]),
            'keterangan' => 'Test keterangan'
        ];

        $response = $this->postJson("/api/pemetaan/fasilitas/{$nonExistentId}", $data);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal membuat pemetaan fasilitas'
            ]);
    }

    /** @test */
    public function it_handles_invalid_geojson_during_store()
    {
        $pemetaanTanah = PemetaanTanah::factory()->create();

        $data = [
            'jenis_fasilitas' => 'Bergerak',
            'kategori_fasilitas' => 'Test Kategori',
            'nama_fasilitas' => 'Test Fasilitas',
            'jenis_geometri' => 'POINT',
            'geometri' => 'invalid-json',
            'keterangan' => 'Test keterangan'
        ];

        $response = $this->postJson("/api/pemetaan/fasilitas/{$pemetaanTanah->id_pemetaan_tanah}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['geometri']);
    }

    /** @test */
    public function it_handles_general_errors_during_show()
    {
        // Mock exception
        $mock = Mockery::mock(PemetaanFasilitas::class);
        $mock->shouldReceive('with->findOrFail')->andThrow(new \Exception('Test Error'));
        $this->app->instance(PemetaanFasilitas::class, $mock);

        $response = $this->getJson('/api/pemetaan/fasilitas-detail/fake-id');

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal mengambil data pemetaan fasilitas'
            ]);
    }

    /** @test */
    public function it_logs_error_when_store_fails()
    {
        $pemetaanTanah = PemetaanTanah::factory()->create();

        // Mock Log dengan lebih fleksibel
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Pemetaan Fasilitas - Store Error') ||
                    str_contains($message, 'Test Error'); // Menerima pesan error yang berbeda
            });

        // Mock DB transaction untuk throw exception
        DB::shouldReceive('beginTransaction')->once()->andThrow(new \Exception('Test Error'));

        $data = [
            'jenis_fasilitas' => 'Bergerak',
            'kategori_fasilitas' => 'Test Kategori',
            'nama_fasilitas' => 'Test Fasilitas',
            'jenis_geometri' => 'POINT',
            'geometri' => json_encode(['type' => 'Point', 'coordinates' => [106.0, -6.0]]),
            'keterangan' => 'Test keterangan'
        ];

        $response = $this->postJson("/api/pemetaan/fasilitas/{$pemetaanTanah->id_pemetaan_tanah}", $data);

        // Verifikasi response juga untuk memastikan alur berjalan benar
        $response->assertStatus(500);
    }

    /** @test */
    public function it_has_required_relationships()
    {
        $user = User::factory()->create();
        $fasilitas = PemetaanFasilitas::factory()->create(['id_user' => $user->id]);

        $this->assertInstanceOf(PemetaanTanah::class, $fasilitas->pemetaanTanah);
        $this->assertInstanceOf(User::class, $fasilitas->user);
    }

    /** @test */
    public function it_returns_correct_structure_for_public_show()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $response = $this->getJson("/api/fasilitas/public/{$fasilitas->id_pemetaan_fasilitas}");

        $response->assertJsonStructure([
            'status',
            'data' => [
                'id_pemetaan_fasilitas',
                'id_pemetaan_tanah',
                'id_user',
                'jenis_fasilitas',
                'kategori_fasilitas',
                'nama_fasilitas',
                'jenis_geometri',
                'geometri',
                'keterangan',
                'created_at',
                'updated_at',
                'pemetaan_tanah'
            ]
        ]);
    }

    /** @test */
    public function it_handles_invalid_geojson_structure_during_store()
    {
        $pemetaanTanah = PemetaanTanah::factory()->create();

        $data = [
            'jenis_fasilitas' => 'Bergerak',
            'kategori_fasilitas' => 'Test Kategori',
            'nama_fasilitas' => 'Test Fasilitas',
            'jenis_geometri' => 'POINT',
            'geometri' => json_encode(['type' => 'Invalid', 'coordinates' => 'invalid']),
            'keterangan' => 'Test keterangan'
        ];

        $response = $this->postJson("/api/pemetaan/fasilitas/{$pemetaanTanah->id_pemetaan_tanah}", $data);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal membuat pemetaan fasilitas'
            ]);
    }

    /** @test */
    public function it_handles_validation_errors_during_public_show()
    {
        $invalidId = 'invalid-uuid';

        $response = $this->getJson("/api/fasilitas/public/{$invalidId}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_validation_errors_during_show_detail()
    {
        $invalidId = 'invalid-uuid';

        $response = $this->getJson("/api/pemetaan/fasilitas-detail/{$invalidId}");

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal mengambil data pemetaan fasilitas'
            ]);
    }

    /** @test */
    public function it_handles_empty_response_for_public_by_pemetaan_tanah()
    {
        $nonExistentId = \Illuminate\Support\Str::uuid();

        $response = $this->getJson("/api/pemetaan/{$nonExistentId}/fasilitas/public");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function it_handles_empty_response_for_public_by_jenis()
    {
        $response = $this->getJson('/api/fasilitas/jenis/NonExistentType/public');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function it_handles_empty_response_for_user_pemetaan_fasilitas()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/pemetaan/user/pemetaan-fasilitas/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function it_handles_validation_errors_during_update_with_invalid_geometry()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $data = [
            'jenis_geometri' => 'POINT',
            'geometri' => 'invalid-json'
        ];

        $response = $this->putJson("/api/pemetaan/fasilitas/{$fasilitas->id_pemetaan_fasilitas}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['geometri']);
    }

    /** @test */
    public function it_handles_geometry_conversion_errors_during_update()
    {
        $fasilitas = PemetaanFasilitas::factory()->create();

        $data = [
            'jenis_geometri' => 'POINT',
            'geometri' => json_encode(['type' => 'Invalid', 'coordinates' => 'invalid'])
        ];

        $response = $this->putJson("/api/pemetaan/fasilitas/{$fasilitas->id_pemetaan_fasilitas}", $data);

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal memperbarui pemetaan fasilitas'
            ]);
    }

    /** @test */
    public function it_handles_validation_errors_during_user_pemetaan_fasilitas()
    {
        $invalidUserId = 'invalid-id';

        $response = $this->getJson("/api/pemetaan/user/pemetaan-fasilitas/{$invalidUserId}");

        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal mengambil data pemetaan fasilitas'
            ]);
    }

    /** @test */
    public function it_handles_validation_errors_during_user_pemetaan_fasilitas_detail()
    {
        $user = User::factory()->create();
        $invalidId = 'invalid-id';

        $response = $this->getJson("/api/pemetaan/user/pemetaan-fasilitas/{$user->id}/{$invalidId}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data pemetaan fasilitas tidak ditemukan atau tidak memiliki akses'
            ]);
    }

    /** @test */
    public function it_converts_point_geojson_to_wkt()
    {
        $controller = new \App\Http\Controllers\PemetaanFasilitasController();

        $geojson = [
            'type' => 'Point',
            'coordinates' => [1, 2]
        ];

        $result = $this->invokePrivateMethod($controller, 'geojsonToWkt', [$geojson, 'POINT']);

        $this->assertEquals('POINT(1 2)', $result);
    }

    /** @test */
    public function it_throws_exception_for_invalid_geojson_during_conversion()
    {
        $this->expectException(\Exception::class);

        $controller = new \App\Http\Controllers\PemetaanFasilitasController();
        $this->invokePrivateMethod($controller, 'geojsonToWkt', [['type' => 'Invalid'], 'POINT']);
    }

}
