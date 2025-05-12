<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\User;
use App\Models\PemetaanFasilitas;
use App\Models\PemetaanTanah;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Str;

class PemetaanFasilitasObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Auth facade is properly mocked before each test
        Auth::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Mock ActivityLog::create to work around GeometryCast issues
     */
    protected function mockActivityLogCreate()
    {
        $activityLogMock = Mockery::mock('alias:App\Models\ActivityLog');
        $activityLogMock->shouldReceive('create')->andReturnUsing(function ($data) {
            // Create ActivityLog manually to avoid casting issues
            return DB::table('activity_logs')->insert([
                'user_id' => $data['user_id'],
                'action' => $data['action'],
                'model_type' => $data['model_type'],
                'model_id' => $data['model_id'],
                'changes' => $data['changes'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Helper method to create a simple geometry
     */
    protected function createPointGeometry($lat = 0, $long = 0)
    {
        return DB::raw("ST_GeomFromText('POINT($long $lat)', 4326)");
    }

    /**
     * Helper method to create a polygon geometry
     */
    protected function createPolygonGeometry()
    {
        // Simple polygon
        return DB::raw("ST_GeomFromText('POLYGON((0 0, 0 1, 1 1, 1 0, 0 0))', 4326)");
    }

    /**
     * Test that activity log is created when a PemetaanFasilitas model is created
     */
    public function test_log_created_when_pemetaan_fasilitas_created()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Mock ActivityLog to handle geometry data properly
        $this->mockActivityLogCreate();

        // Create pemetaan_fasilitas without triggering the observer
        $pemetaanFasilitas = new PemetaanFasilitas();
        $pemetaanFasilitas->id_pemetaan_fasilitas = Str::uuid();
        $pemetaanFasilitas->id_pemetaan_tanah = Str::uuid();
        $pemetaanFasilitas->id_user = $user->id;
        $pemetaanFasilitas->jenis_fasilitas = 'Tidak Bergerak';
        $pemetaanFasilitas->kategori_fasilitas = 'Bangunan';
        $pemetaanFasilitas->nama_fasilitas = 'Gedung Utama';
        $pemetaanFasilitas->keterangan = 'Bangunan utama kantor';
        $pemetaanFasilitas->jenis_geometri = 'POLYGON';

        // For testing purposes, we'll set geometri to a simplified representation
        // In a real scenario, we'd mock the geometry data properly
        $geometriMock = json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [0, 1], [1, 1], [1, 0], [0, 0]]]]);
        $pemetaanFasilitas->geometri = $geometriMock;

        // Manually call the observer method
        (new \App\Observers\PemetaanFasilitasObserver)->created($pemetaanFasilitas);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'create',
            'model_type' => PemetaanFasilitas::class,
            'model_id' => $pemetaanFasilitas->id_pemetaan_fasilitas,
        ]);
    }

    /**
     * Test that activity log is created when a PemetaanFasilitas model is updated
     */
    public function test_log_updated_when_pemetaan_fasilitas_updated()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Mock ActivityLog to handle geometry data properly
        $this->mockActivityLogCreate();

        // Create pemetaan_fasilitas without triggering observer
        $pemetaanFasilitas = new PemetaanFasilitas();
        $pemetaanFasilitas->id_pemetaan_fasilitas = Str::uuid();
        $pemetaanFasilitas->id_pemetaan_tanah = Str::uuid();
        $pemetaanFasilitas->id_user = $user->id;
        $pemetaanFasilitas->jenis_fasilitas = 'Tidak Bergerak';
        $pemetaanFasilitas->kategori_fasilitas = 'Bangunan';
        $pemetaanFasilitas->nama_fasilitas = 'Initial nama fasilitas';
        $pemetaanFasilitas->keterangan = 'Initial keterangan';
        $pemetaanFasilitas->jenis_geometri = 'POLYGON';

        // Simulate update by setting changes array
        $pemetaanFasilitas->nama_fasilitas = 'Updated nama fasilitas';
        $changes = ['nama_fasilitas' => 'Updated nama fasilitas'];

        // Use reflection to set the changes array
        $reflection = new \ReflectionObject($pemetaanFasilitas);
        $property = $reflection->getProperty('changes');
        $property->setAccessible(true);
        $property->setValue($pemetaanFasilitas, $changes);

        // Manually call the observer method
        (new \App\Observers\PemetaanFasilitasObserver)->updated($pemetaanFasilitas);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update',
            'model_type' => PemetaanFasilitas::class,
            'model_id' => $pemetaanFasilitas->id_pemetaan_fasilitas,
        ]);
    }

    /**
     * Test that activity log is created when a PemetaanFasilitas model is deleted
     */
    public function test_log_deleted_when_pemetaan_fasilitas_deleted()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Mock ActivityLog to handle geometry data properly
        $this->mockActivityLogCreate();

        // Create pemetaan_fasilitas without triggering observer
        $pemetaanFasilitas = new PemetaanFasilitas();
        $pemetaanFasilitas->id_pemetaan_fasilitas = Str::uuid();
        $pemetaanFasilitas->id_pemetaan_tanah = Str::uuid();
        $pemetaanFasilitas->id_user = $user->id;
        $pemetaanFasilitas->jenis_fasilitas = 'Tidak Bergerak';
        $pemetaanFasilitas->kategori_fasilitas = 'Bangunan';
        $pemetaanFasilitas->nama_fasilitas = 'Gedung Utama';
        $pemetaanFasilitas->keterangan = 'Bangunan utama kantor';
        $pemetaanFasilitas->jenis_geometri = 'POLYGON';

        // For testing purposes, we'll set geometri to a simplified representation
        $geometriMock = json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [0, 1], [1, 1], [1, 0], [0, 0]]]]);
        $pemetaanFasilitas->geometri = $geometriMock;

        // Manually call the observer method
        (new \App\Observers\PemetaanFasilitasObserver)->deleted($pemetaanFasilitas);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delete',
            'model_type' => PemetaanFasilitas::class,
            'model_id' => $pemetaanFasilitas->id_pemetaan_fasilitas,
        ]);
    }

}
