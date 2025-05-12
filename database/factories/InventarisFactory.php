<?php

namespace Database\Factories;

use App\Models\Inventaris;
use App\Models\Fasilitas;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventarisFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Inventaris::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_inventaris' => Str::uuid(),
            'id_fasilitas' => function () {
                // Create necessary parent records and return fasilitas ID
                $pemetaanId = Str::uuid();
                $tanahId = Str::uuid();
                $fasilitasId = Str::uuid();

                DB::table('pemetaan_fasilitas')->insert([
                    'id_pemetaan_fasilitas' => $pemetaanId,
                    'nama' => $this->faker->word,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('tanahs')->insert([
                    'id_tanah' => $tanahId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('fasilitas')->insert([
                    'id_fasilitas' => $fasilitasId,
                    'id_pemetaan_fasilitas' => $pemetaanId,
                    'id_tanah' => $tanahId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $fasilitasId;
            },
            'nama_barang' => $this->faker->words(3, true),
            'kode_barang' => $this->faker->optional()->bothify('INV-####'),
            'satuan' => $this->faker->randomElement(['Unit', 'Buah', 'Set', 'Paket', 'Lembar']),
            'jumlah' => $this->faker->numberBetween(1, 100),
            'detail' => $this->faker->optional()->paragraph(),
            'waktu_perolehan' => $this->faker->optional()->date(),
            'kondisi' => $this->faker->randomElement(['baik', 'rusak_ringan', 'rusak_berat', 'hilang']),
            'catatan' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the inventaris is in good condition.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function kondisiBaik()
    {
        return $this->state(function (array $attributes) {
            return [
                'kondisi' => 'baik',
            ];
        });
    }

    /**
     * Indicate that the inventaris is slightly damaged.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function kondisiRusakRingan()
    {
        return $this->state(function (array $attributes) {
            return [
                'kondisi' => 'rusak_ringan',
            ];
        });
    }

    /**
     * Indicate that the inventaris is heavily damaged.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function kondisiRusakBerat()
    {
        return $this->state(function (array $attributes) {
            return [
                'kondisi' => 'rusak_berat',
            ];
        });
    }

    /**
     * Indicate that the inventaris is missing.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function kondisiHilang()
    {
        return $this->state(function (array $attributes) {
            return [
                'kondisi' => 'hilang',
            ];
        });
    }
}
