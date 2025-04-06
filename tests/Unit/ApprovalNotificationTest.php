<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Approval;
use App\Notifications\ApprovalNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ApprovalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_via_channels()
    {
        $approval = Approval::factory()->create();
        $notification = new ApprovalNotification($approval, 'create', 'bidgar');

        $this->assertEquals(['database'], $notification->via(new User()));
    }

    public function test_to_array_for_bidgar_create_action()
    {
        $user = User::factory()->create(['name' => 'Pimpinan A']);
        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'type' => 'sertifikat',
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan A'])
        ]);

        $notification = new ApprovalNotification($approval, 'create', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertEquals(
            "Penambahan data sertifikat oleh Pimpinan A.",
            $array['message']
        );
        $this->assertEquals('sertifikat', $array['type']);
        $this->assertEquals($approval->status, $array['status']);
        $this->assertArrayHasKey('details', $array);
    }

    public function test_to_array_for_bidgar_update_action()
    {
        $user = User::factory()->create(['name' => 'Pimpinan B']);
        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'type' => 'sertifikat_update',
            'data' => json_encode([
                'previous_data' => ['field' => 'old'],
                'updated_data' => ['field' => 'new']
            ])
        ]);

        $notification = new ApprovalNotification($approval, 'update', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertEquals(
            "Pembaharuan data sertifikat oleh Pimpinan B.",
            $array['message']
        );
        $this->assertArrayHasKey('previous_data', $array['details']);
        $this->assertArrayHasKey('updated_data', $array['details']);
    }

    public function test_to_array_for_pimpinan_jamaah_create_action()
    {
        $approval = Approval::factory()->create([
            'type' => 'tanah',
            'approver_id' => User::factory()->create()->id,
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan C'])
        ]);

        $notification = new ApprovalNotification($approval, 'create', 'pimpinan_jamaah');
        $array = $notification->toArray(new User());

        $this->assertEquals(
            "Bidgar Wakaf telah menyetujui pembuatan data tanah.",
            $array['message']
        );
    }

    public function test_to_array_for_pimpinan_jamaah_update_action()
    {
        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'approver_id' => User::factory()->create()->id,
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan D'])
        ]);

        $notification = new ApprovalNotification($approval, 'update', 'pimpinan_jamaah');
        $array = $notification->toArray(new User());

        $this->assertEquals(
            "Bidgar Wakaf telah menyetujui pembaharuan data tanah.",
            $array['message']
        );
    }

    public function test_nama_pimpinan_fallback_to_user_name()
    {
        $user = User::factory()->create(['name' => 'Pimpinan Fallback']);
        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'type' => 'sertifikat',
            'data' => json_encode([]) // Tidak ada NamaPimpinanJamaah
        ]);

        $notification = new ApprovalNotification($approval, 'create', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertStringContainsString(
            "Pimpinan Fallback",
            $array['message']
        );
    }

    public function test_nama_pimpinan_fallback_to_unknown()
    {
        $approval = Approval::factory()->create([
            'user_id' => Str::uuid(), // User tidak ada
            'type' => 'sertifikat',
            'data' => json_encode([]) // Tidak ada NamaPimpinanJamaah
        ]);

        $notification = new ApprovalNotification($approval, 'create', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertStringContainsString(
            "Unknown",
            $array['message']
        );
    }

    public function test_username_and_approvername_in_output()
    {
        $user = User::factory()->create(['name' => 'User Test']);
        $approver = User::factory()->create(['name' => 'Approver Test']);

        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'approver_id' => $approver->id,
            'type' => 'sertifikat',
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan E'])
        ]);

        $notification = new ApprovalNotification($approval, 'create', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertEquals('User Test', $array['username']);
        $this->assertEquals('Approver Test', $array['approvername']);
    }
}
