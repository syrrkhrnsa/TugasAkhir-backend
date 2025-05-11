<?php

namespace Database\Factories;

use App\Models\Inventaris;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventarisFactory extends Factory
{
    protected $model = Inventaris::class;

    public function definition()
    {
        return [
            'id_inventaris' => \Illuminate\Support\Str::uuid(),
            'id_fasilitas' => \App\Models\Fasilitas::factory(),
            'nama_barang' => $this->faker->word,
            'kode_barang' => $this->faker->word,
            'satuan' => $this->faker->word,
            'jumlah' => $this->faker->numberBetween(1, 100),
            'detail' => $this->faker->sentence,
            'waktu_perolehan' => $this->faker->date(),
            'kondisi' => $this->faker->randomElement(['baik', 'rusak_ringan', 'rusak_berat', 'hilang']),
            'catatan' => $this->faker->sentence,
        ];
    }
}
