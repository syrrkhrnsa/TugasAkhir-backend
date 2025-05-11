<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition()
    {
        $user = User::factory()->create();

        return [
            'model_type' => 'App\Models\Tanah',
            'model_id' => Str::uuid(),
            'user_id' => $user->id,
            'action' => 'create',
            'changes' => json_encode(['nama' => 'Contoh']),
        ];
    }
}
