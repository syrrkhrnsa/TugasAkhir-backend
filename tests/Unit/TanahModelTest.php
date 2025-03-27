<?php

namespace Tests\Unit\Models;

use App\Models\Tanah;
use App\Models\Sertifikat;
use App\Models\User;
use App\Models\Approval;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

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

    public function testSertifikatRelationship()
    {
        $relation = $this->tanah->sertifikat();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $relation);
        $this->assertEquals('tanah_id', $relation->getForeignKeyName());
        $this->assertEquals('id_tanah', $relation->getLocalKeyName());
        $this->assertInstanceOf(Sertifikat::class, $relation->getRelated());
    }

    public function testUserRelationship()
    {
        $relation = $this->tanah->user();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function testApprovalsRelationship()
    {
        $relation = $this->tanah->approvals();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('data_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Approval::class, $relation->getRelated());
    }
}
