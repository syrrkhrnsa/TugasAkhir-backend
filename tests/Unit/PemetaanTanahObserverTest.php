<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\User;
use App\Models\PemetaanTanah;
use App\Models\Tanah;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Str;

class PemetaanTanahObserverTest extends TestCase
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
     * Helper method to create a polygon geometry
     */
    protected function createPolygonGeometry()
    {
        // Simple polygon representing a land parcel
        return DB::raw("ST_GeomFromText('POLYGON((107.6 -6.9, 107.6 -6.8, 107.7 -6.8, 107.7 -6.9, 107.6 -6.9))', 4326)");
    }

    /**
     * Helper method to create a multipolygon geometry
     */
    protected function createMultiPolygonGeometry()
    {
        // MultiPolygon with two simple polygons
        return DB::raw("ST_GeomFromText('MULTIPOLYGON(((107.6 -6.9, 107.6 -6.8, 107.7 -6.8, 107.7 -6.9, 107.6 -6.9)), ((107.8 -6.9, 107.8 -6.8, 107.9 -6.8, 107.9 -6.9, 107.8 -6.9)))', 4326)");
    }

    /**
     * Test that activity log is created when a PemetaanTanah model is created
     */
    public function test_log_created_when_pemetaan_tanah_created()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Mock ActivityLog to handle geometry data properly
        $this->mockActivityLogCreate();

        // Create a simplified PemetaanTanah instance without triggering the observer
        $pemetaanTanah = new PemetaanTanah();
        $pemetaanTanah->id_pemetaan_tanah = Str::uuid();
        $pemetaanTanah->id_tanah = Str::uuid();
        $pemetaanTanah->id_user = $user->id;
        $pemetaanTanah->nama_pemetaan = 'Pemetaan Tanah Wakaf';
        $pemetaanTanah->keterangan = 'Pemetaan tanah wakaf untuk masjid';
        $pemetaanTanah->jenis_geometri = 'POLYGON';
        $pemetaanTanah->luas_tanah = 1250.75;

        // For testing purposes, set geometri to a simplified GeoJSON representation
        $geometriMock = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[[107.6, -6.9], [107.6, -6.8], [107.7, -6.8], [107.7, -6.9], [107.6, -6.9]]]
        ]);
        $pemetaanTanah->geometri = $geometriMock;

        // Manually call the observer method
        (new \App\Observers\PemetaanTanahObserver)->created($pemetaanTanah);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'create',
            'model_type' => PemetaanTanah::class,
            'model_id' => $pemetaanTanah->id_pemetaan_tanah,
        ]);
    }

    /**
     * Test that activity log is created when a PemetaanTanah model is updated
     */
    public function test_log_updated_when_pemetaan_tanah_updated()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Mock ActivityLog to handle geometry data properly
        $this->mockActivityLogCreate();

        // Create pemetaan_tanah instance without triggering observer
        $pemetaanTanah = new PemetaanTanah();
        $pemetaanTanah->id_pemetaan_tanah = Str::uuid();
        $pemetaanTanah->id_tanah = Str::uuid();
        $pemetaanTanah->id_user = $user->id;
        $pemetaanTanah->nama_pemetaan = 'Original nama pemetaan';
        $pemetaanTanah->keterangan = 'Original keterangan';
        $pemetaanTanah->jenis_geometri = 'POLYGON';
        $pemetaanTanah->luas_tanah = 1000.00;

        // Simulate update by setting changes array
        $pemetaanTanah->nama_pemetaan = 'Updated nama pemetaan';
        $changes = ['nama_pemetaan' => 'Updated nama pemetaan'];

        // Use reflection to set the changes array
        $reflection = new \ReflectionObject($pemetaanTanah);
        $property = $reflection->getProperty('changes');
        $property->setAccessible(true);
        $property->setValue($pemetaanTanah, $changes);

        // Manually call the observer method
        (new \App\Observers\PemetaanTanahObserver)->updated($pemetaanTanah);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update',
            'model_type' => PemetaanTanah::class,
            'model_id' => $pemetaanTanah->id_pemetaan_tanah,
        ]);
    }

    /**
     * Test that activity log is created when a PemetaanTanah model is deleted
     */
    public function test_log_deleted_when_pemetaan_tanah_deleted()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Mock ActivityLog to handle geometry data properly
        $this->mockActivityLogCreate();

        // Create pemetaan_tanah without triggering observer
        $pemetaanTanah = new PemetaanTanah();
        $pemetaanTanah->id_pemetaan_tanah = Str::uuid();
        $pemetaanTanah->id_tanah = Str::uuid();
        $pemetaanTanah->id_user = $user->id;
        $pemetaanTanah->nama_pemetaan = 'Pemetaan Tanah Wakaf';
        $pemetaanTanah->keterangan = 'Pemetaan untuk dokumentasi batas tanah';
        $pemetaanTanah->jenis_geometri = 'POLYGON';
        $pemetaanTanah->luas_tanah = 1250.75;

        // For testing purposes, set geometri to a simplified representation
        $geometriMock = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[[107.6, -6.9], [107.6, -6.8], [107.7, -6.8], [107.7, -6.9], [107.6, -6.9]]]
        ]);
        $pemetaanTanah->geometri = $geometriMock;

        // Manually call the observer method
        (new \App\Observers\PemetaanTanahObserver)->deleted($pemetaanTanah);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delete',
            'model_type' => PemetaanTanah::class,
            'model_id' => $pemetaanTanah->id_pemetaan_tanah,
        ]);
    }
}
