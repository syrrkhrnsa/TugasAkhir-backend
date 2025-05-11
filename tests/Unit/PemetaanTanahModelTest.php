<?php

namespace Tests\Unit\Models;

use App\Models\PemetaanTanah;
use App\Models\Tanah;
use App\Models\User;
use App\Models\PemetaanFasilitas;
use App\Casts\GeometryCast;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class PemetaanTanahModelTest extends TestCase
{
    protected $pemetaanTanah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pemetaanTanah = new PemetaanTanah();
    }

    public function testFillableAttributes()
    {
        $expected = [
            'id_pemetaan_tanah',
            'id_tanah',
            'id_user',
            'nama_pemetaan',
            'keterangan',
            'jenis_geometri',
            'geometri',
            'luas_tanah',
        ];

        $this->assertEquals($expected, $this->pemetaanTanah->getFillable());
    }

    public function testPrimaryKeyConfiguration()
    {
        $this->assertEquals('id_pemetaan_tanah', $this->pemetaanTanah->getKeyName());
        $this->assertFalse($this->pemetaanTanah->getIncrementing());
        $this->assertEquals('string', $this->pemetaanTanah->getKeyType());
    }

    public function testTableName()
    {
        $this->assertEquals('pemetaan_tanah', $this->pemetaanTanah->getTable());
    }

    public function testCasts()
    {
        $expected = [
            'geometri' => GeometryCast::class
        ];

        $this->assertEquals($expected, $this->pemetaanTanah->getCasts());
    }

    public function testTanahRelationship()
    {
        $relation = $this->pemetaanTanah->tanah();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_tanah', $relation->getForeignKeyName());
        $this->assertEquals('id_tanah', $relation->getOwnerKeyName());
        $this->assertInstanceOf(Tanah::class, $relation->getRelated());
    }

    public function testUserRelationship()
    {
        $relation = $this->pemetaanTanah->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_user', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function testFasilitasRelationship()
    {
        $relation = $this->pemetaanTanah->fasilitas();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('id_pemetaan_tanah', $relation->getForeignKeyName());
        $this->assertEquals('id_pemetaan_tanah', $relation->getLocalKeyName());
        $this->assertInstanceOf(PemetaanFasilitas::class, $relation->getRelated());
    }


}
