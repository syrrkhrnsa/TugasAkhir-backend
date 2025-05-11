<?php

namespace Database\Factories;

use App\Models\PemetaanTanah;
use App\Models\Tanah;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Factories\Factory;

class PemetaanTanahFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PemetaanTanah::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id_tanah' => function () {
                return Tanah::factory()->create()->id_tanah;
            },
            'id_user' => function () {
                return User::factory()->create()->id;
            },
            'id_pemetaan_tanah' => (string) Str::uuid(),
            'nama_pemetaan' => $this->faker->word . ' ' . $this->faker->word,
            'keterangan' => $this->faker->paragraph,
            'jenis_geometri' => 'MULTIPOLYGON',
            'geometri' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [106.0, -6.0],
                    [106.1, -6.0],
                    [106.1, -6.1],
                    [106.0, -6.1],
                    [106.0, -6.0]
                ]]
            ],
            'luas_tanah' => $this->faker->randomFloat(2, 100, 10000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
