<?php

namespace Tests\Unit\Models;

use App\Models\DokumenLegalitas;
use App\Models\Sertifikat;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class DokumenLegalitasModelTest extends TestCase
{
    protected $dokumenLegalitas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dokumenLegalitas = new DokumenLegalitas();
    }

    public function testFillableAttributes()
    {
        $expected = [
            'id_sertifikat',
            'dokumen_legalitas'
        ];

        $this->assertEquals($expected, $this->dokumenLegalitas->getFillable());
    }

    public function testPrimaryKeyConfiguration()
    {
        $this->assertEquals('id_dokumen_legalitas', $this->dokumenLegalitas->getKeyName());
        $this->assertFalse($this->dokumenLegalitas->getIncrementing());
        $this->assertEquals('string', $this->dokumenLegalitas->getKeyType());
    }

    public function testTableName()
    {
        $this->assertEquals('dokumen_legalitas', $this->dokumenLegalitas->getTable());
    }

    public function testSertifikatRelationship()
    {
        // Test relasi belongsTo dengan Sertifikat
        $relation = $this->dokumenLegalitas->sertifikat();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_sertifikat', $relation->getForeignKeyName());
        $this->assertEquals('id_sertifikat', $relation->getOwnerKeyName());
        $this->assertInstanceOf(Sertifikat::class, $relation->getRelated());
    }

    public function testCreateDokumenLegalitas()
    {
        // Buat sertifikat dummy
        $sertifikat = Sertifikat::factory()->create();

        // Data untuk dokumen legalitas
        $data = [
            'id_sertifikat' => $sertifikat->id_sertifikat,
            'dokumen_legalitas' => 'dokumen.pdf'
        ];

        // Buat dokumen legalitas
        $dokumen = DokumenLegalitas::create($data);

        // Assertions
        $this->assertDatabaseHas('dokumen_legalitas', $data);
        $this->assertEquals($sertifikat->id_sertifikat, $dokumen->id_sertifikat);
        $this->assertEquals('dokumen.pdf', $dokumen->dokumen_legalitas);
    }
}
