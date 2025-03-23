<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class RoleModelTest extends TestCase
{
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->role = new Role();
    }

    public function testFillableAttributes()
    {
        $fillable = ['id', 'name'];
        $this->assertEquals($fillable, $this->role->getFillable());
    }

    public function testPrimaryKey()
    {
        $this->assertEquals('id', $this->role->getKeyName());
    }

    public function testIncrementingIsFalse()
    {
        $this->assertFalse($this->role->incrementing);
    }

    public function testKeyTypeIsString()
    {
        $this->assertEquals('string', $this->role->getKeyType());
    }

    public function testUsersRelationship()
    {
        $relation = $this->role->users();
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('role_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(User::class, $relation->getRelated());
    }
}
