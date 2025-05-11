<?php

namespace Tests\Unit\Models;

use App\Models\Fasilitas;
use App\Models\PemetaanFasilitas;
use App\Models\Tanah;
use App\Models\Inventaris;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class FasilitasModelTest extends TestCase
{
    protected $fasilitas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fasilitas = new Fasilitas();
    }

    public function testFillableAttributes()
    {
        $expected = [
            'id_fasilitas',
            'id_pemetaan_fasilitas',
            'id_tanah',
            'file_360',
            'file_gambar',
            'file_pdf',
            'catatan'
        ];

        $this->assertEquals($expected, $this->fasilitas->getFillable());
    }

    public function testPrimaryKeyConfiguration()
    {
        $this->assertEquals('id_fasilitas', $this->fasilitas->getKeyName());
        $this->assertFalse($this->fasilitas->getIncrementing());
        $this->assertEquals('string', $this->fasilitas->getKeyType());
    }

    public function testTableName()
    {
        $this->assertEquals('fasilitas', $this->fasilitas->getTable());
    }

    public function testPemetaanFasilitasRelationship()
    {
        $relation = $this->fasilitas->pemetaanFasilitas();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_pemetaan_fasilitas', $relation->getForeignKeyName());
        $this->assertEquals('id_pemetaan_fasilitas', $relation->getOwnerKeyName());
        $this->assertInstanceOf(PemetaanFasilitas::class, $relation->getRelated());
    }

    public function testTanahRelationship()
    {
        $relation = $this->fasilitas->tanah();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_tanah', $relation->getForeignKeyName());
        $this->assertEquals('id_tanah', $relation->getOwnerKeyName());
        $this->assertInstanceOf(Tanah::class, $relation->getRelated());
    }

    public function testInventarisRelationship()
    {
        $relation = $this->fasilitas->inventaris();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('id_fasilitas', $relation->getForeignKeyName());
        $this->assertEquals('id_fasilitas', $relation->getLocalKeyName());
        $this->assertInstanceOf(Inventaris::class, $relation->getRelated());
    }


}
