<?php

namespace Tests\Unit\Models;

use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class ApprovalModelTest extends TestCase
{
    use RefreshDatabase; // Tambahkan ini

    protected $approval;

    protected function setUp(): void
    {
        parent::setUp();
        $this->approval = new Approval();
    }

    public function testFillableAttributes()
    {
        $fillable = ['user_id', 'approver_id', 'type', 'data_id', 'data', 'status'];
        $this->assertEquals($fillable, $this->approval->getFillable());
    }

    public function testIncrementingIsFalse()
    {
        $this->assertFalse($this->approval->incrementing);
    }

    public function testKeyTypeIsString()
    {
        $this->assertEquals('string', $this->approval->getKeyType());
    }

    public function testUuidIsGeneratedOnCreate()
    {
        $approval = Approval::create([
            'user_id' => Str::uuid(),
            'approver_id' => Str::uuid(),
            'type' => 'example_type',
            'data_id' => Str::uuid(),
            'data' => 'example_data',
            'status' => 'ditinjau',
        ]);

        $this->assertNotNull($approval->id);
    }

    public function testUserRelationship()
    {
        $relation = $this->approval->user();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }

    public function testApproverRelationship()
    {
        $relation = $this->approval->approver();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('approver_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }
}
