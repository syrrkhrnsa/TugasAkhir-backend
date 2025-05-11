<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Fasilitas;
use App\Models\ActivityLog;
use App\Models\Tanah;
use App\Models\PemetaanFasilitas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class FasilitasObserverTest extends TestCase
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
     * Test that activity log is created when a Fasilitas model is created
     */
    public function test_log_created_when_fasilitas_created()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Create fasilitas without triggering the observer
        $fasilitas = new Fasilitas();
        $fasilitas->id_fasilitas = \Illuminate\Support\Str::uuid();
        $fasilitas->id_pemetaan_fasilitas = \Illuminate\Support\Str::uuid();
        $fasilitas->id_tanah = \Illuminate\Support\Str::uuid();
        $fasilitas->catatan = 'Test catatan';

        // Manually call the observer method
        (new \App\Observers\FasilitasObserver)->created($fasilitas);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'create',
            'model_type' => Fasilitas::class,
            'model_id' => $fasilitas->id_fasilitas,
        ]);
    }

    /**
     * Test that activity log is created when a Fasilitas model is updated
     */
    public function test_log_updated_when_fasilitas_updated()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Create fasilitas without triggering observer
        $fasilitas = new Fasilitas();
        $fasilitas->id_fasilitas = \Illuminate\Support\Str::uuid();
        $fasilitas->id_pemetaan_fasilitas = \Illuminate\Support\Str::uuid();
        $fasilitas->id_tanah = \Illuminate\Support\Str::uuid();
        $fasilitas->catatan = 'Initial catatan';

        // Simulate update by setting changes array
        $fasilitas->catatan = 'Updated catatan';
        $changes = ['catatan' => 'Updated catatan'];

        // Use reflection to set the changes array
        $reflection = new \ReflectionObject($fasilitas);
        $property = $reflection->getProperty('changes');
        $property->setAccessible(true);
        $property->setValue($fasilitas, $changes);

        // Manually call the observer method
        (new \App\Observers\FasilitasObserver)->updated($fasilitas);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update',
            'model_type' => Fasilitas::class,
            'model_id' => $fasilitas->id_fasilitas,
        ]);
    }

    /**
     * Test that activity log is created when a Fasilitas model is deleted
     */
    public function test_log_deleted_when_fasilitas_deleted()
    {
        $user = User::factory()->create();

        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($user->id);

        // Create fasilitas without triggering observer
        $fasilitas = new Fasilitas();
        $fasilitas->id_fasilitas = \Illuminate\Support\Str::uuid();
        $fasilitas->id_pemetaan_fasilitas = \Illuminate\Support\Str::uuid();
        $fasilitas->id_tanah = \Illuminate\Support\Str::uuid();
        $fasilitas->catatan = 'Test catatan';

        // Manually call the observer method
        (new \App\Observers\FasilitasObserver)->deleted($fasilitas);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'delete',
            'model_type' => Fasilitas::class,
            'model_id' => $fasilitas->id_fasilitas,
        ]);
    }


}
