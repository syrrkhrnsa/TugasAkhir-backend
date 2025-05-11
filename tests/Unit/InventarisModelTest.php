<?php

namespace Tests\Unit\Models;

use App\Models\Inventaris;
use App\Models\Fasilitas;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InventarisModelTest extends TestCase
{
    protected $inventaris;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventaris = new Inventaris();
    }

    public function testFillableAttributes()
    {
        $expected = [
            'id_inventaris',
            'id_fasilitas',
            'nama_barang',
            'kode_barang',
            'satuan',
            'jumlah',
            'detail',
            'waktu_perolehan',
            'kondisi',
            'catatan',
        ];

        $this->assertEquals($expected, $this->inventaris->getFillable());
    }

    public function testPrimaryKeyConfiguration()
    {
        $this->assertEquals('id_inventaris', $this->inventaris->getKeyName());
        $this->assertFalse($this->inventaris->getIncrementing());
        $this->assertEquals('string', $this->inventaris->getKeyType());
    }

    public function testTableName()
    {
        $this->assertEquals('inventaris', $this->inventaris->getTable());
    }

    public function testCasts()
    {
        $expected = [
            'jumlah' => 'integer',
            'waktu_perolehan' => 'date',
        ];

        $this->assertEquals($expected, $this->inventaris->getCasts());
    }

    public function testFasilitasRelationship()
    {
        $relation = $this->inventaris->fasilitas();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_fasilitas', $relation->getForeignKeyName());
        $this->assertEquals('id_fasilitas', $relation->getOwnerKeyName());
        $this->assertInstanceOf(Fasilitas::class, $relation->getRelated());
    }


}
