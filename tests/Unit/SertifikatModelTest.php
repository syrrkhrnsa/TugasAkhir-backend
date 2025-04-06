<?php

namespace Tests\Unit\Models;

use App\Models\Sertifikat;
use App\Models\Tanah;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class SertifikatModelTest extends TestCase
{
    protected $sertifikat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sertifikat = new Sertifikat();
    }

    public function testFillableAttributes()
    {
        $expected = [
            'id_sertifikat',
            'no_dokumen',
            'dokumen',
            'jenis_sertifikat',
            'status_pengajuan',
            'tanggal_pengajuan',
            'status',
            'user_id',
            'id_tanah'
        ];
        $this->assertEquals($expected, $this->sertifikat->getFillable());
    }

    public function testPrimaryKey()
    {
        $this->assertEquals('id_sertifikat', $this->sertifikat->getKeyName());
    }

    public function testIncrementingIsFalse()
    {
        $this->assertFalse($this->sertifikat->incrementing);
    }

    public function testKeyTypeIsString()
    {
        $this->assertEquals('string', $this->sertifikat->getKeyType());
    }

    public function testTanahRelationship()
    {
        $relation = $this->sertifikat->tanah();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_tanah', $relation->getForeignKeyName());
        $this->assertInstanceOf(Tanah::class, $relation->getRelated());
    }

    public function testUserRelationship()
    {
        $relation = $this->sertifikat->user();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function testApprovalsRelationship()
    {
        $relation = $this->sertifikat->approvals();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('data_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Approval::class, $relation->getRelated());
    }
}
