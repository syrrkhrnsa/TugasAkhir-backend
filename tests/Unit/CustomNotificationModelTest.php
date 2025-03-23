<?php

namespace Tests\Unit\Models;

use App\Models\CustomNotification;
use App\Models\Approval;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class CustomNotificationModelTest extends TestCase
{
    protected $customNotification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customNotification = new CustomNotification();
    }

    public function testInheritsFromDatabaseNotification()
    {
        $this->assertInstanceOf(DatabaseNotification::class, $this->customNotification);
    }

    public function testApprovalRelationship()
    {
        $relation = $this->customNotification->approval();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('approval_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Approval::class, $relation->getRelated());
    }
}
