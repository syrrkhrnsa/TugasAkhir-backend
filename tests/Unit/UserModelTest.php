<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use App\Models\Tanah;
use App\Models\Sertifikat;
use App\Models\CustomNotification;
use Mockery;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UserModelTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testFillableAttributes()
    {
        $fillable = ['id', 'name', 'username', 'email', 'password', 'role_id'];
        $this->assertEquals($fillable, $this->user->getFillable());
    }

    public function testHiddenAttributes()
    {
        $hidden = ['password', 'remember_token'];
        $this->assertEquals($hidden, $this->user->getHidden());
    }

    public function testCastsAttributes()
    {
        $casts = ['email_verified_at' => 'datetime'];
        $this->assertEquals($casts, $this->user->getCasts());
    }

    public function testIncrementingIsFalse()
    {
        $this->assertFalse($this->user->incrementing);
    }

    public function testKeyTypeIsString()
    {
        $this->assertEquals('string', $this->user->getKeyType());
    }

    public function testRoleRelationship()
    {
        $relation = $this->user->role();
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Role::class, $relation->getRelated());
    }

    public function testActivityLogsRelationship()
    {
        $relation = $this->user->activityLogs();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(ActivityLog::class, $relation->getRelated());
    }

    public function testTanahRelationship()
    {
        $relation = $this->user->tanah();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Tanah::class, $relation->getRelated());
    }

    public function testSertifikatRelationship()
    {
        $relation = $this->user->sertifikat();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Sertifikat::class, $relation->getRelated());
    }

    public function testNotificationsRelationship()
    {
        $relation = $this->user->notifications();
        $this->assertInstanceOf(MorphMany::class, $relation);
        $this->assertInstanceOf(CustomNotification::class, $relation->getRelated());
    }
}
