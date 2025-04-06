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
            'no_dokumen' => $this->faker->numerify('BASTW-####'),
            'status' => 'ditinjau',
            'user_id' => User::factory(),
            'dokumen' => null,
            'jenis_sertifikat'=> 'BASTW',
            'status_pengajuan' => 'Terbit',
            'tanggal_pengajuan' => now()->format('Y-m-d'),
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
