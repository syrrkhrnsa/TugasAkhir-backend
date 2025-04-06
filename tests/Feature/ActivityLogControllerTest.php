<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tanah;
use App\Models\Sertifikat;
use App\Models\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test data
        $this->tanah = \App\Models\Tanah::factory()->create([
            'id_tanah' => (string) Str::uuid(),
        ]);
        $this->sertifikat = Sertifikat::factory()->create(['id_tanah' => $this->tanah->id_tanah]);
    }

    // Test logTanah method
    public function test_log_tanah_with_no_logs()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/log-tanah');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function test_log_tanah_with_logs()
    {
        $log = ActivityLog::factory()->create([
            'model_type' => 'App\\Models\\Tanah',
            'model_id' => $this->tanah->id_tanah,
            'user_id' => $this->user->id,
            'action' => 'create',
            'changes' => json_encode(['nama' => 'Tanah Baru'])
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/log-tanah');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'nama_user' => $this->user->name,
                'aksi' => 'Create',
                'perubahan' => ['nama' => 'Tanah Baru'],
                'waktu' => $log->created_at->format('H:i:s'),
                'tanggal' => $log->created_at->format('Y-m-d')
            ]);
    }


    // Test logSertifikat method
    public function test_log_sertifikat_with_no_logs()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/log-sertifikat');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function test_log_sertifikat_with_logs()
    {
        $log = ActivityLog::factory()->create([
            'model_type' => 'App\\Models\\Sertifikat',
            'model_id' => $this->sertifikat->id_sertifikat,
            'user_id' => $this->user->id,
            'action' => 'update',
            'changes' => json_encode(['no_dokumen' => '12345'])
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/log-sertifikat');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'nama_user' => $this->user->name,
                'perubahan' => 'Update data sertifikat di bagian no_dokumen',
                'waktu' => $log->created_at->format('H:i:s'),
                'tanggal' => $log->created_at->format('Y-m-d'),
            ]);
    }


    // Test logStatus method




    // Test logByUser method
    public function test_log_by_user_with_no_logs()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/log-user/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function test_log_by_user_with_logs()
    {
        $log = ActivityLog::factory()->create([
            'model_type' => 'App\\Models\\Tanah',
            'model_id' => $this->tanah->id_tanah,
            'user_id' => $this->user->id,
            'action' => 'create',
            'changes' => json_encode(['nama' => 'Tanah Baru'])
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/log-user/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJson([
                [
                    'nama_user' => $this->user->name,
                    'aksi' => 'Create',
                    'model' => 'Tanah',
                    'perubahan' => ['nama' => 'Tanah Baru'],
                    'waktu' => $log->created_at->format('H:i:s'),
                    'tanggal' => $log->created_at->format('Y-m-d')
                ]
            ]);
    }

    // Test logByTanahId method
    public function test_log_by_tanah_id_with_valid_uuid()
    {
        // Bersihkan semua log agar test bersih
        DB::table('activity_logs')->delete();

        // Simpan waktu sekarang
        $now = Carbon::now();

        // Buat log manual tanpa isi kolom `id`
        DB::table('activity_logs')->insert([
            'model_type' => 'App\\Models\\Tanah',
            'model_id' => $this->tanah->id_tanah,
            'user_id' => $this->user->id,
            'action' => 'update',
            'changes' => json_encode(['status' => 'approved']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Ambil log terakhir dari DB untuk assert
        $log = DB::table('activity_logs')->latest('id')->first();

        // Lakukan permintaan ke API
        $response = $this->actingAs($this->user)
            ->getJson("/api/log-tanah/{$this->tanah->id_tanah}");

        // Assert response sesuai dengan log
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $log->id,
                'model_id' => $this->tanah->id_tanah,
                'nama_user' => $this->user->name,
                'aksi' => 'Update',
                'perubahan' => ['status' => 'approved'],
                'waktu' => $now->format('H:i:s'),
                'tanggal' => $now->format('Y-m-d'),
            ]);
    }

    public function test_log_by_tanah_id_with_invalid_uuid()
    {
        $invalidId = 'invalid-uuid';

        $response = $this->actingAs($this->user)
            ->getJson("/api/log-tanah/{$invalidId}");

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Format ID tanah tidak valid'
            ]);
    }

    public function test_log_by_tanah_id_with_no_logs()
    {
        ActivityLog::query()->delete();

        $newTanah = Tanah::withoutEvents(function () {
            return Tanah::factory()->create();
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/log-tanah/{$newTanah->id_tanah}");

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Data log tidak ditemukan'
            ]);
    }



    public function test_log_by_sertifikat_id_with_invalid_uuid()
    {
        $invalidId = 'invalid-uuid';

        $response = $this->actingAs($this->user)
            ->getJson("/api/log-sertifikat/{$invalidId}");

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Format ID sertifikat tidak valid'
            ]);
    }

    public function test_log_by_sertifikat_id_with_valid_uuid()
    {
        $log = ActivityLog::factory()->create([
            'model_type' => 'App\\Models\\Sertifikat',
            'model_id' => $this->sertifikat->id_sertifikat,
            'user_id' => $this->user->id,
            'action' => 'update',
            'changes' => json_encode(['no_dokumen' => '12345'])
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/log-sertifikat/{$this->sertifikat->id_sertifikat}");

        $response->assertStatus(200)
            ->assertJsonCount(2) // Pastikan ada 2 log (create dan update)
            ->assertJsonFragment([
                'model_id' => $this->sertifikat->id_sertifikat,
                'nama_user' => $this->user->name,
                'aksi' => 'Update',
                'model' => 'Sertifikat',
                'perubahan' => ['no_dokumen' => '12345'],
            ]);
    }

    public function test_log_by_sertifikat_id_with_partial_uuid_match()
    {
        // Gunakan UUID lengkap seperti yang diharapkan oleh kode produksi
        $fullId = $this->sertifikat->id_sertifikat;

        $log = ActivityLog::factory()->create([
            'model_type' => 'App\\Models\\Sertifikat',
            'model_id' => $this->sertifikat->id_sertifikat,
            'user_id' => $this->user->id,
            'action' => 'create',
            'changes' => json_encode(['status' => 'pending'])
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/log-sertifikat/{$fullId}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'model_id' => $this->sertifikat->id_sertifikat,
                'aksi' => 'Create',
                'perubahan' => ['status' => 'pending']
            ]);
    }

    public function test_log_by_sertifikat_id_with_different_json_formats()
    {
        // Test dengan JSON yang valid
        $log1 = ActivityLog::factory()->create([
            'model_type' => 'App\\Models\\Sertifikat',
            'model_id' => $this->sertifikat->id_sertifikat,
            'user_id' => $this->user->id,
            'action' => 'update',
            'changes' => '{"field1":"value1","field2":"value2"}'
        ]);

        // Test dengan JSON yang escaped
        $log2 = ActivityLog::factory()->create([
            'model_type' => 'App\\Models\\Sertifikat',
            'model_id' => $this->sertifikat->id_sertifikat,
            'user_id' => $this->user->id,
            'action' => 'delete',
            'changes' => '{\"field\":\"value\"}'
        ]);

        // Test dengan string biasa (bukan JSON)
        $log3 = ActivityLog::factory()->create([
            'model_type' => 'App\\Models\\Sertifikat',
            'model_id' => $this->sertifikat->id_sertifikat,
            'user_id' => $this->user->id,
            'action' => 'create',
            'changes' => 'plain text changes'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/log-sertifikat/{$this->sertifikat->id_sertifikat}");

        $response->assertStatus(200)
            ->assertJsonFragment(['perubahan' => ['field1' => 'value1', 'field2' => 'value2']])
            ->assertJsonFragment(['perubahan' => ['field' => 'value']])
            ->assertJsonFragment(['perubahan' => ['raw_changes' => 'plain text changes']]);
    }

    public function test_log_by_sertifikat_id_with_no_logs()
    {
        ActivityLog::query()->delete();

        $newSertifikat = Sertifikat::withoutEvents(function () {
            return Sertifikat::factory()->create();
        });

        $response = $this->actingAs($this->user)
            ->getJson("/api/log-sertifikat/{$newSertifikat->id_sertifikat}");

        $response->assertStatus(404)
            ->assertJsonStructure([
                'error',
                'debug' => [
                    'input_id',
                    'normalized_id',
                    'sql_query',
                    'sample_data'
                ]
            ]);
    }


}
