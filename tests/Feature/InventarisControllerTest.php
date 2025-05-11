<?php

namespace Tests\Feature;

use App\Http\Controllers\InventarisController;
use App\Models\Inventaris;
use App\Models\Fasilitas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Models\Tanah;
use App\Models\PemetaanFasilitas;


class InventarisControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;



    /** @test */
    public function testIndexReturnsAllInventaris()
    {
        $inventaris = Inventaris::factory()->count(3)->create();
        $response = $this->getJson('/api/inventaris');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

}
