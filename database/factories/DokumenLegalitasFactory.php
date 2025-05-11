<?php

namespace Database\Factories;

use App\Models\DokumenLegalitas;
use App\Models\Sertifikat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DokumenLegalitasFactory extends Factory
{
    protected $model = DokumenLegalitas::class;

    public function definition(): array
    {
        return [
            'id_dokumen_legalitas' => (string) Str::uuid(),
            'id_sertifikat' => Sertifikat::factory(), // membuat sertifikat secara otomatis jika belum ada
            'dokumen_legalitas' => 'sertifikat/dokumen/' . $this->faker->unique()->word . '.pdf',
        ];
    }
}
