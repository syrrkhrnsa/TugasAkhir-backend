<?php

namespace Tests\Unit\Models;

use App\Models\PemetaanFasilitas;
use App\Models\PemetaanTanah;
use App\Models\User;
use App\Casts\GeometryCast;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class PemetaanFasilitasModelTest extends TestCase
{
    protected $pemetaanFasilitas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pemetaanFasilitas = new PemetaanFasilitas();
    }

    public function testFillableAttributes()
    {
        $expected = [
            'id_pemetaan_fasilitas',
            'id_pemetaan_tanah',
            'id_user',
            'jenis_fasilitas',
            'kategori_fasilitas',
            'nama_fasilitas',
            'keterangan',
            'jenis_geometri',
            'geometri'
        ];

        $this->assertEquals($expected, $this->pemetaanFasilitas->getFillable());
    }

    public function testPrimaryKeyConfiguration()
    {
        $this->assertEquals('id_pemetaan_fasilitas', $this->pemetaanFasilitas->getKeyName());
        $this->assertFalse($this->pemetaanFasilitas->getIncrementing());
        $this->assertEquals('string', $this->pemetaanFasilitas->getKeyType());
    }

    public function testTableName()
    {
        $this->assertEquals('pemetaan_fasilitas', $this->pemetaanFasilitas->getTable());
    }

    public function testCasts()
    {
        $expected = [
            'geometri' => GeometryCast::class,
            'jenis_fasilitas' => 'string'
        ];

        $this->assertEquals($expected, $this->pemetaanFasilitas->getCasts());
    }

    public function testJenisFasilitasConstants()
    {
        $this->assertEquals('Bergerak', PemetaanFasilitas::JENIS_BERGERAK);
        $this->assertEquals('Tidak Bergerak', PemetaanFasilitas::JENIS_TIDAK_BERGERAK);
    }

    public function testPemetaanTanahRelationship()
    {
        $relation = $this->pemetaanFasilitas->pemetaanTanah();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_pemetaan_tanah', $relation->getForeignKeyName());
        $this->assertEquals('id_pemetaan_tanah', $relation->getOwnerKeyName());
        $this->assertInstanceOf(PemetaanTanah::class, $relation->getRelated());
    }

    public function testUserRelationship()
    {
        $relation = $this->pemetaanFasilitas->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_user', $relation->getForeignKeyName());
        $this->assertEquals('id', $relation->getOwnerKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }


}
