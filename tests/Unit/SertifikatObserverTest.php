<?php
namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sertifikat;
use App\Models\ActivityLog;
use App\Models\Approval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SertifikatObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_created_when_sertifikat_created()
    {
        $user = User::factory()->create(['role_id' => '326f0dde-2851-4e47-ac5a-de6923447317']);
        Auth::shouldReceive('user')->andReturn($user);

        $sertifikat = Sertifikat::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'create',
            'model_type' => Sertifikat::class,
            'model_id' => $sertifikat->id_sertifikat,
        ]);
    }

    public function test_log_updated_when_sertifikat_updated()
    {
        $user = User::factory()->create(['role_id' => '326f0dde-2851-4e47-ac5a-de6923447317']);
        Auth::shouldReceive('user')->andReturn($user);

        $sertifikat = Sertifikat::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft'
        ]);

        $sertifikat->status = 'disetujui';
        $sertifikat->save();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'update',
            'model_type' => Sertifikat::class,
            'model_id' => $sertifikat->id_sertifikat,
        ]);
    }

    public function test_log_deleted_when_sertifikat_deleted()
    {
        $user = User::factory()->create();

        Auth::shouldReceive('id')->andReturn($user->id);
        Auth::shouldReceive('user')->andReturn($user); // ← tambahkan ini

        $sertifikat = Sertifikat::factory()->create(['user_id' => $user->id]);

        $sertifikat->delete();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'delete',
            'model_type' => Sertifikat::class,
            'model_id' => $sertifikat->id_sertifikat,
        ]);
    }


    public function test_bidgarwakaf_user_uses_user_id_from_approval_json()
    {
        $bidgar = User::factory()->create(['role_id' => '26b2b64e-9ae3-4e2e-9063-590b1bb00480']);
        $pimpinanJamaah = User::factory()->create();

        Auth::shouldReceive('user')->andReturn($bidgar);

        $sertifikat = Sertifikat::factory()->create(['user_id' => $pimpinanJamaah->id]);

        Approval::factory()->create([
            'data_id' => $sertifikat->id_sertifikat,
            'type' => 'tanah_dan_sertifikat',
            'data' => json_encode(['user_id' => $pimpinanJamaah->id]),
            'user_id' => $bidgar->id,
            'status' => 'ditinjau',
        ]);

        $sertifikat->status = 'update-testing';
        $sertifikat->save();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $pimpinanJamaah->id,
            'action' => 'update',
            'model_id' => $sertifikat->id_sertifikat,
        ]);
    }

}
