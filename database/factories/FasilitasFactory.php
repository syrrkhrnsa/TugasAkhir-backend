<?php

namespace Database\Factories;

use App\Models\Fasilitas;
use App\Models\Tanah;
use App\Models\PemetaanFasilitas;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class FasilitasFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Fasilitas::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_fasilitas' => $this->faker->uuid(),
            'id_pemetaan_fasilitas' => function () {
                return DB::table('pemetaan_fasilitas')->insertGetId(
                    [
                        'id_pemetaan_fasilitas' => $this->faker->uuid(),
                        'nama' => $this->faker->word,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    'id_pemetaan_fasilitas'
                );
            },
            'id_tanah' => function () {
                return DB::table('tanahs')->insertGetId(
                    [
                        'id_tanah' => $this->faker->uuid(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    'id_tanah'
                );
            },
            'file_360' => null,
            'file_gambar' => null,
            'file_pdf' => null,
            'catatan' => $this->faker->paragraph(),
        ];
    }
}
