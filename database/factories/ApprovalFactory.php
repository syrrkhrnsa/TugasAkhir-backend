<?php

namespace Database\Factories;

use App\Models\Approval;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApprovalFactory extends Factory
{
    protected $model = Approval::class;

    public function definition()
    {
        return [
            'data_id' => Str::uuid(),
            'id' => Str::uuid(),
            'type' => 'tanah_dan_sertifikat',
            'data' => json_encode([
                'tanah' => ['NamaPimpinanJamaah' => 'Test'],
                'sertifikat' => ['nomor' => '123']
            ]),
            'status' => 'ditinjau',
            'user_id' => \App\Models\User::factory(),
            'approver_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
