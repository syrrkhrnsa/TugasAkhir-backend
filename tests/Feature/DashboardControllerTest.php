<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tanah;
use App\Models\Sertifikat;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user (you can adjust the role as needed)
        $this->user = User::factory()->create();
    }

    public function test_get_dashboard_stats_with_no_data()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total_tanah' => 0,
                'jenis_sertifikat' => []
            ]);
    }

    public function test_get_dashboard_stats_with_tanah_data_only()
    {
        // Create tanah records without sertifikat
        Tanah::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total_tanah' => 3,
                'jenis_sertifikat' => []
            ]);
    }

    public function test_get_dashboard_stats_with_sertifikat_data()
    {
        // Create tanah and sertifikat records
        $tanah = Tanah::factory()->create();

        // Create different types of sertifikat
        Sertifikat::factory()->create(['jenis_sertifikat' => 'BASTW', 'id_tanah' => $tanah->id_tanah]);
        Sertifikat::factory()->create(['jenis_sertifikat' => 'BASTW', 'id_tanah' => $tanah->id_tanah]);
        Sertifikat::factory()->create(['jenis_sertifikat' => 'AIW', 'id_tanah' => $tanah->id_tanah]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total_tanah' => 1,
                'jenis_sertifikat' => [
                    'BASTW' => 2,
                    'AIW' => 1
                ]
            ]);
    }

    public function test_get_dashboard_stats_with_multiple_tanah_and_sertifikat()
    {
        // Create 5 tanah records
        Tanah::factory()->count(5)->create();

        // Create 3 tanah records with sertifikat
        $tanahWithSertifikat = Tanah::factory()->count(3)->create();

        // Add multiple sertifikat for each tanah
        foreach ($tanahWithSertifikat as $tanah) {
            Sertifikat::factory()->count(2)->create([
                'jenis_sertifikat' => 'BASTW',
                'id_tanah' => $tanah->id_tanah
            ]);
            Sertifikat::factory()->create([
                'jenis_sertifikat' => 'AIW',
                'id_tanah' => $tanah->id_tanah
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total_tanah' => 8, // 5 + 3
                'jenis_sertifikat' => [
                    'BASTW' => 6, // 3 tanah * 2 BASTW each
                    'AIW' => 3     // 3 tanah * 1 AIW each
                ]
            ]);
    }

    public function test_get_dashboard_stats_with_different_sertifikat_types()
    {
        $tanah = Tanah::factory()->create();

        $sertifikatTypes = ['BASTW', 'AIW', 'SHM', 'HGB'];
        foreach ($sertifikatTypes as $type) {
            Sertifikat::factory()->create([
                'jenis_sertifikat' => $type,
                'id_tanah' => $tanah->id_tanah
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total_tanah' => 1,
                'jenis_sertifikat' => [
                    'BASTW' => 1,
                    'AIW' => 1,
                    'SHM' => 1,
                    'HGB' => 1
                ]
            ]);
    }

    public function test_response_structure()
    {
        Tanah::factory()->count(2)->create();
        Sertifikat::factory()->create(['jenis_sertifikat' => 'BASTW']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_tanah',
                'jenis_sertifikat' => [
                    'BASTW'
                ]
            ]);
    }

    public function test_unauthenticated_access()
    {
        $response = $this->getJson('/api/dashboard/stats');
        $response->assertStatus(401);
    }
}
