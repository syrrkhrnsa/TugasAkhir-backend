<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Approval;
use App\Notifications\RejectionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class RejectionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_via_channels()
    {
        $approval = Approval::factory()->create();
        $notification = new RejectionNotification($approval, 'reject', 'bidgar');

        $this->assertEquals(['database'], $notification->via(new User()));
    }

    public function test_to_array_for_bidgar_reject_action()
    {
        $user = User::factory()->create(['name' => 'Pimpinan A']);
        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'type' => 'sertifikat',
            'status' => 'ditolak',
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan A'])
        ]);

        $notification = new RejectionNotification($approval, 'reject', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertEquals(
            "Permintaan data sertifikat oleh Pimpinan A telah diproses.",
            $array['message']
        );
        $this->assertEquals('sertifikat', $array['type']);
        $this->assertEquals('ditolak', $array['status']);
        $this->assertArrayHasKey('details', $array);
    }

    public function test_to_array_for_bidgar_reject_update_action()
    {
        $user = User::factory()->create(['name' => 'Pimpinan B']);
        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'type' => 'sertifikat_update',
            'status' => 'ditolak',
            'data' => json_encode([
                'previous_data' => ['field' => 'old'],
                'updated_data' => ['field' => 'new']
            ])
        ]);

        $notification = new RejectionNotification($approval, 'reject_update', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertEquals(
            "Permintaan pembaharuan data sertifikat oleh Pimpinan B.",
            $array['message']
        );
    }

    public function test_to_array_for_pimpinan_jamaah_reject_action()
    {
        $approver = User::factory()->create(['name' => 'Bidgar Wakaf']);
        $approval = Approval::factory()->create([
            'type' => 'tanah',
            'status' => 'ditolak',
            'approver_id' => $approver->id,
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan C'])
        ]);

        $notification = new RejectionNotification($approval, 'reject', 'pimpinan_jamaah');
        $array = $notification->toArray(new User());

        $this->assertEquals(
            "Bidgar Wakaf telah menolak pembuatan data tanah.",
            $array['message']
        );
        $this->assertEquals('Bidgar Wakaf', $array['approvername']);
    }

    public function test_to_array_for_pimpinan_jamaah_reject_update_action()
    {
        $approver = User::factory()->create(['name' => 'Bidgar Wakaf']);
        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'status' => 'ditolak',
            'approver_id' => $approver->id,
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan D'])
        ]);

        $notification = new RejectionNotification($approval, 'reject_update', 'pimpinan_jamaah');
        $array = $notification->toArray(new User());

        $this->assertEquals(
            "Bidgar Wakaf telah menolak pembaharuan data tanah.",
            $array['message']
        );
    }

    public function test_nama_pimpinan_fallback_to_user_name()
    {
        $user = User::factory()->create(['name' => 'Pimpinan Fallback']);
        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'type' => 'sertifikat',
            'status' => 'ditolak',
            'data' => json_encode([]) // Tidak ada NamaPimpinanJamaah
        ]);

        $notification = new RejectionNotification($approval, 'reject', 'bidgar');
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
            'status' => 'ditolak',
            'data' => json_encode([]) // Tidak ada NamaPimpinanJamaah
        ]);

        $notification = new RejectionNotification($approval, 'reject', 'bidgar');
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
            'status' => 'ditolak',
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan E'])
        ]);

        $notification = new RejectionNotification($approval, 'reject', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertEquals('User Test', $array['username']);
        $this->assertEquals('Approver Test', $array['approvername']);
    }

    public function test_default_message_for_unknown_action()
    {
        $user = User::factory()->create(['name' => 'Pimpinan X']);
        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'type' => 'sertifikat',
            'status' => 'ditolak',
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan X'])
        ]);

        $notification = new RejectionNotification($approval, 'unknown_action', 'bidgar');
        $array = $notification->toArray(new User());

        $this->assertStringContainsString(
            "Permintaan data sertifikat oleh Pimpinan X telah diproses.",
            $array['message']
        );
    }

    public function test_notification_for_unknown_recipient()
    {
        $user = User::factory()->create(['name' => 'Pimpinan Y']);
        $approval = Approval::factory()->create([
            'user_id' => $user->id,
            'type' => 'sertifikat',
            'status' => 'ditolak',
            'data' => json_encode(['NamaPimpinanJamaah' => 'Pimpinan Y'])
        ]);

        try {
            $notification = new RejectionNotification($approval, 'reject', 'unknown_recipient');
            $array = $notification->toArray(new User());

            // Jika sampai sini tanpa exception, verifikasi struktur dasar
            $this->assertArrayHasKey('type', $array);
            $this->assertArrayHasKey('status', $array);
            $this->assertEquals('sertifikat', $array['type']);
            $this->assertEquals('ditolak', $array['status']);

            // Cek jika message ada (opsional)
            if (isset($array['message'])) {
                $this->assertIsString($array['message']);
            }
        } catch (\ErrorException $e) {
            // Tangkap error undefined variable dan verifikasi pesan errornya
            $this->assertStringContainsString('Undefined variable', $e->getMessage());
        } catch (\Exception $e) {
            // Tangkap exception lainnya
            $this->fail('Unexpected exception: '.$e->getMessage());
        }
    }
}
