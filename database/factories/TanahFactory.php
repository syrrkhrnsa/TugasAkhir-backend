<?php

namespace Database\Factories;

use App\Models\Tanah;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TanahFactory extends Factory
{
    protected $model = Tanah::class;

    public function definition()
    {
        return [
            'id_tanah' => Str::uuid(),
            'NamaPimpinanJamaah' => $this->faker->name,
            'NamaWakif' => $this->faker->name,
            'lokasi' => $this->faker->address,
            'luasTanah' => $this->faker->numberBetween(100, 1000),
            'legalitas' => 'N/A',
            'status' => 'disetujui',
            'user_id' => function () {
                return \App\Models\User::factory()->create()->id;
            },
        ];
    }
}
