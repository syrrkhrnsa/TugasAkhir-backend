<?php

namespace Tests\Unit\Models;

use App\Models\Tanah;
use App\Models\Sertifikat;
use App\Models\User;
use App\Models\Approval;
use App\Models\PemetaanTanah;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;
use App\Casts\GeometryCast;
use Illuminate\Support\Str;

class TanahModelTest extends TestCase
{
    protected $tanah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tanah = new Tanah();
    }

    public function testFillableAttributes()
    {
        $fillable = [
            'id_tanah',
            'NamaPimpinanJamaah',
            'NamaWakif',
            'lokasi',
            'luasTanah',
            'legalitas',
            'status',
            'user_id',
            'jenis_tanah',
            'batas_timur',
            'batas_selatan',
            'batas_barat',
            'batas_utara',
            'panjang_tanah',
            'lebar_tanah',
            'catatan',
            'alamat_wakif',
            'koordinat',
            'latitude',
            'longitude'
        ];
        $this->assertEquals($fillable, $this->tanah->getFillable());
    }

    public function testPrimaryKey()
    {
        $this->assertEquals('id_tanah', $this->tanah->getKeyName());
    }

    public function testIncrementingIsFalse()
    {
        $this->assertFalse($this->tanah->incrementing);
    }

    public function testKeyTypeIsString()
    {
        $this->assertEquals('string', $this->tanah->getKeyType());
    }

    public function testCasts()
    {
        $expected = [
            'koordinat' => GeometryCast::class,
            'latitude' => 'double',
            'longitude' => 'double'
        ];
        $this->assertEquals($expected, $this->tanah->getCasts());
    }

    public function testSertifikatsRelationship()
    {
        $relation = $this->tanah->sertifikats();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('id_tanah', $relation->getForeignKeyName());
        $this->assertEquals('id_tanah', $relation->getLocalKeyName());
        $this->assertInstanceOf(Sertifikat::class, $relation->getRelated());
    }

    public function testUserRelationship()
    {
        $relation = $this->tanah->user();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function testApprovalsRelationship()
    {
        $relation = $this->tanah->approvals();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('data_id', $relation->getForeignKeyName());
        $this->assertEquals('id_tanah', $relation->getLocalKeyName());
        $this->assertInstanceOf(Approval::class, $relation->getRelated());
    }

    public function testPemetaanTanahRelationship()
    {
        $relation = $this->tanah->pemetaanTanah();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('id_tanah', $relation->getForeignKeyName());
        $this->assertEquals('id_tanah', $relation->getLocalKeyName());
        $this->assertInstanceOf(PemetaanTanah::class, $relation->getRelated());
    }
    public function testNearbyScope()
    {
        // Create a real query builder instance
        $query = $this->tanah->newQuery();

        // Apply the scope
        $result = $this->tanah->scopeNearby($query, -7.123, 110.123, 1000);

        // Get the compiled SQL with bindings
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Assert the SQL contains the expected function
        $this->assertStringContainsString('ST_DWithin', $sql);
        $this->assertStringContainsString('ST_SetSRID(ST_MakePoint', $sql);

        // Assert the bindings are correct
        $this->assertEquals([110.123, -7.123, 1000], $bindings);

        // Verify the query builder is returned
        $this->assertSame($query, $result);
    }

    public function testSavingEventSetsKoordinatFromLatLong()
    {
        $user = User::factory()->create();

        // Test case 1: When both latitude and longitude are set
        $tanah1 = new Tanah([
            'id_tanah' => Str::uuid(),
            'NamaPimpinanJamaah' => 'Test Pimpinan 1',
            'NamaWakif' => 'Test Wakif 1',
            'lokasi' => 'Test Lokasi 1',
            'luasTanah' => 100,
            'legalitas' => 'SHM',
            'status' => 'aktif',
            'user_id' => $user->id,
            'jenis_tanah' => 'kering',
            'latitude' => -7.123,
            'longitude' => 110.123
        ]);

        $tanah1->save();

        $this->assertEquals([
            'type' => 'Point',
            'coordinates' => [110.123, -7.123]
        ], $tanah1->koordinat);

        // Test case 2: When only latitude is set
        $tanah2 = new Tanah([
            'id_tanah' => Str::uuid(),
            'NamaPimpinanJamaah' => 'Test Pimpinan 2',
            'NamaWakif' => 'Test Wakif 2',
            'lokasi' => 'Test Lokasi 2',
            'luasTanah' => 200,
            'legalitas' => 'SHM',
            'status' => 'aktif',
            'user_id' => $user->id,
            'jenis_tanah' => 'kering',
            'latitude' => -7.456
        ]);

        $tanah2->save();

        $this->assertNull($tanah2->koordinat);

        // Test case 3: When only longitude is set
        $tanah3 = new Tanah([
            'id_tanah' => Str::uuid(),
            'NamaPimpinanJamaah' => 'Test Pimpinan 3',
            'NamaWakif' => 'Test Wakif 3',
            'lokasi' => 'Test Lokasi 3',
            'luasTanah' => 300,
            'legalitas' => 'SHM',
            'status' => 'aktif',
            'user_id' => $user->id,
            'jenis_tanah' => 'kering',
            'longitude' => 110.456
        ]);

        $tanah3->save();

        $this->assertNull($tanah3->koordinat);

        // Test case 4: When neither is set
        $tanah4 = new Tanah([
            'id_tanah' => Str::uuid(),
            'NamaPimpinanJamaah' => 'Test Pimpinan 4',
            'NamaWakif' => 'Test Wakif 4',
            'lokasi' => 'Test Lokasi 4',
            'luasTanah' => 400,
            'legalitas' => 'SHM',
            'status' => 'aktif',
            'user_id' => $user->id,
            'jenis_tanah' => 'kering'
        ]);

        $tanah4->save();

        $this->assertNull($tanah4->koordinat);
    }
}
