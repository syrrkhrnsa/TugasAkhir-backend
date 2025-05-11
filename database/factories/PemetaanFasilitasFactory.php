<?php

namespace Database\Factories;

use App\Models\PemetaanFasilitas;
use App\Models\PemetaanTanah;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;


class PemetaanFasilitasFactory extends Factory
{
    protected $model = PemetaanFasilitas::class;

    public function definition()
    {
        return [
            'id_pemetaan_fasilitas' => \Illuminate\Support\Str::uuid(),
            'id_pemetaan_tanah' => PemetaanTanah::factory(),
            'id_user' => User::factory(),
            'jenis_fasilitas' => $this->faker->randomElement(['Bergerak', 'Tidak Bergerak']),
            'kategori_fasilitas' => $this->faker->word,
            'nama_fasilitas' => $this->faker->word,
            'keterangan' => $this->faker->sentence,
            'jenis_geometri' => 'POINT',
            'geometri' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [106.0, -6.0],
                    [106.1, -6.0],
                    [106.1, -6.1],
                    [106.0, -6.1],
                    [106.0, -6.0]
                ]]
            ],        ];
    }
}
