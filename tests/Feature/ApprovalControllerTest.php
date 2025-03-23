<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Approval;
use App\Models\Tanah;
use App\Models\Sertifikat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class ApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user dengan role Bidgar Wakaf
        $this->bidgarWakafUser = User::factory()->create([
            'role_id' => '26b2b64e-9ae3-4e2e-9063-590b1bb00480' // Role Bidgar Wakaf
        ]);

        // Buat user biasa (non-Bidgar Wakaf)
        $this->regularUser = User::factory()->create([
            'role_id' => 'bukan-bidgar-wakaf' // Role biasa
        ]);
    }

    // Test untuk method show()
    public function testShowApproval()
    {
        // Buat data approval
        $approval = Approval::factory()->create();

        // Login sebagai Bidgar Wakaf
        $this->actingAs($this->bidgarWakafUser);

        // Panggil method show()
        $response = $this->getJson("/api/approvals/{$approval->id}");

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data permintaan persetujuan ditemukan',
            ]);
    }

    // Test untuk method show() dengan user tidak terautentikasi
    public function testShowApprovalUnauthenticated()
    {
        // Buat data approval
        $approval = Approval::factory()->create();

        // Panggil method show() tanpa login
        $response = $this->getJson("/api/approvals/{$approval->id}");

        // Assert response
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'User tidak terautentikasi',
            ]);
    }

    // Test untuk method show() dengan user yang tidak memiliki izin
    public function testShowApprovalForbidden()
    {
        // Buat data approval
        $approval = Approval::factory()->create();

        // Login sebagai user biasa (non-Bidgar Wakaf)
        $this->actingAs($this->regularUser);

        // Panggil method show()
        $response = $this->getJson("/api/approvals/{$approval->id}");

        // Assert response
        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk melihat detail persetujuan',
            ]);
    }

    // Test untuk method approve()
    public function testApprove()
    {
        // Buat data approval dengan type 'tanah'
        $approval = Approval::factory()->create([
            'type' => 'tanah',
            'data' => json_encode(['luas' => 100, 'lokasi' => 'Jakarta']),
        ]);

        // Login sebagai Bidgar Wakaf
        $this->actingAs($this->bidgarWakafUser);

        // Panggil method approve()
        $response = $this->postJson("/api/approvals/{$approval->id}/approve");

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Permintaan disetujui',
            ]);

        // Assert data tanah telah dibuat
        $this->assertDatabaseHas('tanah', [
            'luas' => 100,
            'lokasi' => 'Jakarta',
            'status' => 'disetujui',
        ]);

        // Assert status approval telah diupdate
        $this->assertDatabaseHas('approvals', [
            'id' => $approval->id,
            'status' => 'disetujui',
        ]);
    }

    // Test untuk method reject()
    public function testReject()
    {
        // Buat data approval dengan type 'sertifikat'
        $approval = Approval::factory()->create([
            'type' => 'sertifikat',
            'data' => json_encode(['nomor' => '12345', 'pemilik' => 'John Doe']),
        ]);

        // Login sebagai Bidgar Wakaf
        $this->actingAs($this->bidgarWakafUser);

        // Panggil method reject()
        $response = $this->postJson("/api/approvals/{$approval->id}/reject");

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Permintaan ditolak dan data disimpan dengan status ditolak',
            ]);

        // Assert data sertifikat telah dibuat dengan status 'ditolak'
        $this->assertDatabaseHas('sertifikat', [
            'nomor' => '12345',
            'pemilik' => 'John Doe',
            'status' => 'ditolak',
        ]);

        // Assert status approval telah diupdate
        $this->assertDatabaseHas('approvals', [
            'id' => $approval->id,
            'status' => 'ditolak',
        ]);
    }

    // Test untuk method getByType()
    public function testGetByType()
    {
        // Buat data approval dengan type 'tanah'
        $approval = Approval::factory()->create([
            'type' => 'tanah',
            'data' => json_encode(['luas' => 100, 'lokasi' => 'Jakarta']),
            'status' => 'ditinjau',
        ]);

        // Login sebagai user
        $this->actingAs($this->bidgarWakafUser);

        // Panggil method getByType()
        $response = $this->getJson("/api/approvals/type/tanah");

        // Assert response
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Data permintaan persetujuan berhasil diambil',
            ])
            ->assertJsonFragment([
                'luas' => 100,
                'lokasi' => 'Jakarta',
                'status' => 'ditinjau',
            ]);
    }
}
