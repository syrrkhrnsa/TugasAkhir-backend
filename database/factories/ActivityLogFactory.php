<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition()
    {
        return [
            'model_type' => 'App\Models\Tanah',
            'model_id' => Str::uuid(),
            'user_id' => null, // akan diisi manual di test
            'action' => 'create',
            'changes' => json_encode(['nama' => 'Contoh']),
        ];
    }
}
