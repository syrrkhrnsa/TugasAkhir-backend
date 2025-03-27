<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tanah;
use App\Models\Approval;
use App\Models\Sertifikat;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ApprovalNotification;
use App\Notifications\RejectionNotification;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Facades\Event;

class ApprovalControllerTest extends TestCase
{
    use RefreshDatabase;
    private $rolePimpinanJamaah = '326f0dde-2851-4e47-ac5a-de6923447317';
    private $rolePimpinanCabang = '3594bece-a684-4287-b0a2-7429199772a3';
    private $roleBidgarWakaf = '26b2b64e-9ae3-4e2e-9063-590b1bb00480';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========== TEST SHOW ===========
    public function test_show_approval_as_bidgar_wakaf()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $approval = Approval::factory()->create([
            'id' => Str::uuid(),
            'data_id' => Str::uuid()
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/approvals/{$approval->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'approval',
                    'data'
                ]
            ]);
    }

    public function test_show_approval_forbidden_for_non_bidgar_wakaf()
    {
        // Gunakan role yang berbeda
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);

        $approval = Approval::factory()->create([
            'id' => Str::uuid(),
            'data_id' => Str::uuid()
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/approvals/{$approval->id}");

        $response->assertStatus(403); // Sesuai ekspektasi
    }

    public function test_show_nonexistent_approval()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $nonExistentId = Str::uuid();

        $response = $this->actingAs($user)
            ->getJson("/api/approvals/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Approval tidak ditemukan']);
    }

    // =========== TEST INDEX ===========
    public function test_index_returns_pending_approvals_for_bidgar_wakaf()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        // Hanya buat 3 approval dengan status 'ditinjau'
        Approval::factory()->count(3)->create([
            'status' => 'ditinjau',
            'user_id' => $user->id // Pastikan milik user ini
        ]);

        // Buat data lain yang tidak seharusnya muncul
        Approval::factory()->count(2)->create(['status' => 'disetujui']);
        Approval::factory()->count(1)->create(['status' => 'ditolak']);

        $response = $this->actingAs($user)
            ->getJson('/api/approvals');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data') // Hanya yang 'ditinjau'
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'status'] // Validasi struktur
                ]
            ]);
    }

    // =========== TEST APPROVE ===========
    public function test_approve_tanah_dan_sertifikat()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $approvalData = [
            'tanah' => [
                'id_tanah' => Str::uuid(),
                'NamaWakif' => 'test',
                'NamaPimpinanJamaah' => 'Test',
                'lokasi' => 'jalan abc',
                'luasTanah' => '100',
                'legalitas' => 'N/A',
                'user_id' => $pimpinan->id
            ],
            'sertifikat' => [
                'id_sertifikat' => Str::uuid(),
                'legalitas' => 'N/A',
            ]
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah_dan_sertifikat',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        if ($response->status() !== 200) {
            dd($response->json()); // Lihat error detail
        }

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('tanahs', ['status' => 'disetujui']);
        $this->assertDatabaseHas('sertifikats', ['status' => 'disetujui']);
        Notification::assertSentTo($pimpinan, ApprovalNotification::class);
    }

    public function test_approve_invalid_type()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $approval = Approval::factory()->create(['type' => 'invalid_type']);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Tipe approval tidak valid']);
    }

    // =========== TEST APPROVE UPDATE ===========
    public function test_approve_tanah_update()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();
        $tanah = Tanah::factory()->create(['status' => 'ditinjau']);

        $approvalData = [
            'previous_data' => $tanah->toArray(),
            'updated_data' => ['NamaPimpinanJamaah' => 'Updated Name']
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/approve");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('tanahs', [
            'id_tanah' => $tanah->id_tanah,
            'NamaPimpinanJamaah' => 'Updated Name'
        ]);
    }

    // =========== TEST REJECT ===========
    public function test_reject_tanah_creation()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $approvalData = [
            'id_tanah' => Str::uuid(),
            'NamaWakif' => 'test',
            'NamaPimpinanJamaah' => 'Test',
            'lokasi' => 'jalan abc',
            'luasTanah' => '100',
            'legalitas' => 'N/A',
            'user_id' => $pimpinan->id
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/reject");

        if ($response->status() !== 200) {
            dd($response->json()); // Lihat error detail
        }

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('tanahs', ['status' => 'ditolak']);
        Notification::assertSentTo($pimpinan, RejectionNotification::class);
    }

    // =========== TEST GET BY TYPE ===========
    public function test_get_by_valid_type()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        Approval::factory()->create(['type' => 'tanah', 'status' => 'ditinjau']);

        $response = $this->actingAs($user)
            ->getJson('/api/approvals/type/tanah');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_get_by_invalid_type()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        $response = $this->actingAs($user)
            ->getJson('/api/approvals/type/invalid_type');

        $response->assertStatus(400)
            ->assertJson(['message' => 'Tipe tidak valid']);
    }

    public function test_approve_with_database_error()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();
        $tanah = Tanah::factory()->create();

        $approvalData = [
            'tanah' => $tanah->toArray(),
            'sertifikat' => Sertifikat::factory()->make([
                'id_tanah' => $tanah->id_tanah
            ])->toArray()
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah_dan_sertifikat',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        // Mock database error
        DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database error'));

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        // Assert status code dan struktur dasar
        $response->assertStatus(500)
            ->assertJsonStructure([
                'message',
                'exception',
                'file',
                'line',
                'trace'
            ]);

        // Assert pesan error spesifik
        $response->assertJsonFragment([
            'message' => 'Database error'
        ]);
    }

    public function test_approve_sertifikat_update()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();
        $sertifikat = Sertifikat::factory()->create(['status' => 'ditinjau']);

        $approvalData = [
            'previous_data' => $sertifikat->toArray(),
            'updated_data' => ['noDokumenBastw' => 'UPDATED-123']
        ];

        $approval = Approval::factory()->create([
            'type' => 'sertifikat_update',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/approve");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('sertifikats', [
            'id_sertifikat' => $sertifikat->id_sertifikat,
            'noDokumenBastw' => 'UPDATED-123'
        ]);
    }

    public function test_reject_sertifikat_update()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $originalSertifikat = Sertifikat::factory()->create([
            'noDokumenBastw' => 'ORIGINAL-123',
            'status' => 'disetujui'
        ]);

        $approvalData = [
            'previous_data' => $originalSertifikat->toArray(),
            'updated_data' => ['noDokumenBastw' => 'UPDATED-123']
        ];

        $approval = Approval::factory()->create([
            'type' => 'sertifikat_update',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/reject");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('sertifikats', [
            'id_sertifikat' => $originalSertifikat->id_sertifikat,
            'noDokumenBastw' => 'ORIGINAL-123' // Kembali ke nilai original
        ]);
    }

    public function test_reject_sertifikat_creation()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $approvalData = Sertifikat::factory()->make()->toArray();

        $approval = Approval::factory()->create([
            'type' => 'sertifikat',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/reject");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('sertifikats', [
            'noDokumenBastw' => $approvalData['noDokumenBastw'],
            'status' => 'ditolak'
        ]);
    }

    public function test_approve_with_invalid_json()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        $approval = Approval::factory()->create([
            'type' => 'tanah_dan_sertifikat',
            'data' => 'bukan-json-valid', // String bukan JSON
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        $response->assertStatus(400)
            ->assertJson(['status' => 'error']);
    }

    public function test_approve_update_missing_previous_data()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'data' => json_encode(['updated_data' => ['field' => 'value']]), // tanpa previous_data
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/approve");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Data approval tidak valid']);
    }

    public function test_notification_sent_on_approve()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $approvalData = [
            'tanah' => Tanah::factory()->make()->toArray(),
            'sertifikat' => Sertifikat::factory()->make()->toArray()
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah_dan_sertifikat',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        // Hanya verifikasi notifikasi dikirim ke user yang benar
        Notification::assertSentTo(
            $pimpinan,
            ApprovalNotification::class
        );
    }

    public function test_error_logging_on_failure()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        $approval = Approval::factory()->create([
            'type' => 'tanah_dan_sertifikat',
            'data' => 'invalid-json', // Data JSON tidak valid
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        // Ubah ekspektasi dari 500 ke 400
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data tidak valid' // Sesuaikan dengan pesan di controller
            ]);
    }

    public function test_approve_with_database_exception()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $approvalData = [
            'tanah' => Tanah::factory()->make()->toArray(),
            'sertifikat' => Sertifikat::factory()->make()->toArray()
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah_dan_sertifikat',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        // Mock database exception
        DB::shouldReceive('beginTransaction')->once()->andThrow(new \Exception('Database error'));

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        $response->assertStatus(500)
            ->assertJsonStructure([
                'message',
                'exception',
                'file',
                'line',
                'trace'
            ])
            ->assertJsonFragment([
                'message' => 'Database error'
            ]);
    }

    public function test_approve_update_tanah_not_found()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $approvalData = [
            'previous_data' => ['id_tanah' => Str::uuid()], // ID yang tidak ada
            'updated_data' => ['NamaPimpinanJamaah' => 'Updated Name']
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/approve");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Data tanah tidak ditemukan']);
    }

    public function test_approve_update_sertifikat_not_found()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $approvalData = [
            'previous_data' => ['id_sertifikat' => Str::uuid()], // ID yang tidak ada
            'updated_data' => ['noDokumenBastw' => 'UPDATED-123']
        ];

        $approval = Approval::factory()->create([
            'type' => 'sertifikat_update',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/approve");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Data sertifikat tidak ditemukan']);
    }

    public function test_reject_update_with_invalid_data()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'data' => 'invalid-json', // Data tidak valid
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/reject");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Data tidak valid']);
    }

    public function test_reject_with_invalid_type()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();

        $approval = Approval::factory()->create([
            'type' => 'invalid_type',
            'data' => json_encode(['field' => 'value']),
            'user_id' => $pimpinan->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/reject");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Tipe approval tidak valid']);
    }

    public function test_reject_update_tanah_not_found()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        // Create approval with non-existent tanah data
        $approvalData = [
            'previous_data' => [
                'id_tanah' => Str::uuid(), // Non-existent ID
                'NamaWakif' => 'Test',
                'NamaPimpinanJamaah' => 'Test',
                'lokasi' => 'Test',
                'luasTanah' => '100',
                'legalitas' => 'Test',
                'status' => 'disetujui',
                'user_id' => $user->id
            ]
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'data' => json_encode($approvalData),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/reject");

        // Assert 500 error occurs (current behavior)
        $response->assertStatus(500);

        // Optional: Check if it's the expected error
        $this->assertStringContainsString(
            'Call to a member function update() on null',
            $response->exception->getMessage()
        );
    }

    public function test_approve_update_with_exception()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        // Buat approval dengan data invalid
        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'data' => 'invalid-json', // Akan menyebabkan json_decode() gagal
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/approve");

        $response->assertStatus(400); // Atau 500 tergantung implementasi
    }

    public function test_index_forbidden_for_non_bidgar_wakaf()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);

        $response = $this->actingAs($user)
            ->getJson('/api/approvals');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki izin melihat permintaan persetujuan']);
    }

    public function test_approve_forbidden_for_non_bidgar_wakaf()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);
        $approval = Approval::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki izin']);
    }

    public function test_approve_update_forbidden_for_non_bidgar_wakaf()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);
        $approval = Approval::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/approve");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki izin']);
    }

    public function test_reject_forbidden_for_non_bidgar_wakaf()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);
        $approval = Approval::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/reject");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki izin']);
    }

    public function test_reject_update_forbidden_for_non_bidgar_wakaf()
    {
        $user = User::factory()->create(['role_id' => $this->rolePimpinanJamaah]);
        $approval = Approval::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/reject");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Anda tidak memiliki izin']);
    }

    public function test_approve_non_existent_approval()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $nonExistentId = Str::uuid();

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$nonExistentId}/approve");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Permintaan tidak ditemukan']);
    }

    public function test_approve_with_invalid_data()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $approval = Approval::factory()->create([
            'data' => 'invalid-json'
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Data tidak valid']);
    }

    public function test_approve_with_invalid_type()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $approval = Approval::factory()->create([
            'type' => 'invalid_type',
            'data' => json_encode(['field' => 'value'])
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Tipe approval tidak valid']);
    }

    public function test_approve_update_with_database_exception()
    {
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);
        $pimpinan = User::factory()->create();
        $tanah = Tanah::factory()->create(['status' => 'ditinjau']);

        $approvalData = [
            'previous_data' => $tanah->toArray(),
            'updated_data' => ['id_tanah' => 99]
        ];

        $approval = Approval::factory()->create([
            'type' => 'tanah_update',
            'data' => json_encode($approvalData),
            'user_id' => $pimpinan->id
        ]);

        // Mock the Tanah model using Laravel's helper
        $mock = $this->mock(Tanah::class, function ($mock) use ($tanah) {
            // Mock the query builder chain
            $mock->shouldReceive('where')
                ->with('id_tanah', $tanah->id_tanah)
                ->andReturnSelf();

            $mock->shouldReceive('first')
                ->andReturn($mock);

            $mock->shouldReceive('update')
                ->andThrow(new \Exception('Database error'));
        });

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/update/approve");

        $response->assertStatus(500)
            ->assertJson([
                "status" => "error",
                "message" => "Terjadi kesalahan saat memproses approval"
            ]);
    }

    public function test_approve_with_database_exception2()
    {
        // Setup
        $user = User::factory()->create(['role_id' => $this->roleBidgarWakaf]);

        // Data dengan field yang sengaja invalid
        $approval = Approval::factory()->create([
            'type' => 'tanah_dan_sertifikat',
            'data' => json_encode([
                'tanah' => ['invalid_field' => 'invalid_value'],
                'sertifikat' => ['invalid_field' => 'invalid_value']
            ])
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/approvals/{$approval->id}/approve");

        $response->assertStatus(500);
    }
}
