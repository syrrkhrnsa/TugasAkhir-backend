<?php

namespace Database\Factories;

use App\Models\Sertifikat;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SertifikatFactory extends Factory
{
    protected $model = Sertifikat::class;

    public function definition()
    {
        return [
            'id_sertifikat' => Str::uuid(),
            'id_tanah' => Tanah::factory(),
            'noDokumenBastw' => $this->faker->numerify('BASTW-####'),
            'noDokumenAIW' => $this->faker->numerify('AIW-####'),
            'noDokumenSW' => $this->faker->numerify('SW-####'),
            'status' => 'ditinjau',
            'legalitas' => 'SHM',
            'user_id' => User::factory(),
            'dokBastw' => null,
            'dokAiw' => null,
            'dokSw' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withTanah(Tanah $tanah)
    {
        return $this->state([
            'id_tanah' => $tanah->id_tanah,
        ]);
    }
}
