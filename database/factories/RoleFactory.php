<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition()
    {
        return [
            'id' => \Illuminate\Support\Str::uuid(), // karena id UUID
            'name' => $this->faker->jobTitle
        ];
    }
}