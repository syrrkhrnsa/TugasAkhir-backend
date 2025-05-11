<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Inventaris;
use App\Models\Fasilitas;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Str;

class InventarisObserverTest extends TestCase
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
     * Test that activity log is created when an Inventaris model is created
     */
    public function test_log_created_when_inventaris_created()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Create inventaris without triggering the observer
        $inventaris = new Inventaris();
        $inventaris->id_inventaris = Str::uuid();
        $inventaris->id_fasilitas = Str::uuid();
        $inventaris->nama_barang = 'Meja Kantor';
        $inventaris->satuan = 'Unit';
        $inventaris->jumlah = 1;
        $inventaris->kondisi = 'baik';

        // Manually call the observer method
        (new \App\Observers\InventarisObserver)->created($inventaris);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'create',
            'model_type' => Inventaris::class,
            'model_id' => $inventaris->id_inventaris,
        ]);
    }

    /**
     * Test that activity log is created when an Inventaris model is updated
     */
    public function test_log_updated_when_inventaris_updated()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Create inventaris without triggering observer
        $inventaris = new Inventaris();
        $inventaris->id_inventaris = Str::uuid();
        $inventaris->id_fasilitas = Str::uuid();
        $inventaris->nama_barang = 'Initial nama barang';
        $inventaris->satuan = 'Unit';
        $inventaris->jumlah = 1;
        $inventaris->kondisi = 'baik';

        // Simulate update by setting changes array
        $inventaris->nama_barang = 'Updated nama barang';
        $changes = ['nama_barang' => 'Updated nama barang'];

        // Use reflection to set the changes array
        $reflection = new \ReflectionObject($inventaris);
        $property = $reflection->getProperty('changes');
        $property->setAccessible(true);
        $property->setValue($inventaris, $changes);

        // Manually call the observer method
        (new \App\Observers\InventarisObserver)->updated($inventaris);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update',
            'model_type' => Inventaris::class,
            'model_id' => $inventaris->id_inventaris,
        ]);
    }

    /**
     * Test that activity log is created when an Inventaris model is deleted
     */
    public function test_log_deleted_when_inventaris_deleted()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Create inventaris without triggering observer
        $inventaris = new Inventaris();
        $inventaris->id_inventaris = Str::uuid();
        $inventaris->id_fasilitas = Str::uuid();
        $inventaris->nama_barang = 'Meja Kantor';
        $inventaris->satuan = 'Unit';
        $inventaris->jumlah = 1;
        $inventaris->kondisi = 'baik';

        // Manually call the observer method
        (new \App\Observers\InventarisObserver)->deleted($inventaris);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delete',
            'model_type' => Inventaris::class,
            'model_id' => $inventaris->id_inventaris,
        ]);
    }

}
