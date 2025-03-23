<?php

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogModelTest extends TestCase
{
    use RefreshDatabase; // Untuk memastikan database di-refresh sebelum setiap test

    protected $activityLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityLog = new ActivityLog();
    }

    public function testFillableAttributes()
    {
        $fillable = [
            'user_id',
            'action',
            'model_type',
            'model_id',
            'changes',
        ];
        $this->assertEquals($fillable, $this->activityLog->getFillable());
    }

    public function testPrimaryKey()
    {
        $this->assertEquals('id', $this->activityLog->getKeyName());
    }

    public function testIncrementingIsFalse()
    {
        $this->assertFalse($this->activityLog->incrementing);
    }

    public function testCastsAttributes()
    {
        $casts = [
            'changes' => 'array',
        ];
        $this->assertEquals($casts, $this->activityLog->getCasts());
    }

    public function testUserRelationship()
    {
        $relation = $this->activityLog->user();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }


}
