<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Tanah;
use App\Models\Sertifikat;

class TanahSertifikatSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::inRandomOrder()->first();

        if (!$user) {
            $this->command->warn('Seeder dilewati karena tidak ada user.');
            return;
        }

        // === Loop Tanah ===
        for ($i = 1; $i <= 7; $i++) {
            $tanah = Tanah::create([
                'id_tanah'             => (string) Str::uuid(),
                'NamaPimpinanJamaah'   => 'Pimpinan Jamaah ' . $i,
                'NamaWakif'            => 'Wakif Ke-' . $i,
                'lokasi'               => 'Lokasi Tanah ' . $i,
                'luasTanah'            => rand(100, 1000) . ' m2',
                'legalitas'            => 'SHM',
                'status'               => 'aktif',
                'user_id'              => $user->id,
                'created_at'           => Carbon::now()->subDays(rand(3, 10)),
                'updated_at'           => Carbon::now()->subDays(rand(3, 10)),
            ]);

            $tanah->update([
                'status' => 'tidak aktif',
            ]);
        }

        // Ambil ulang tanah IDs
        $tanahIds = Tanah::pluck('id_tanah')->toArray();

        // === Loop Sertifikat ===
        for ($i = 1; $i <= 7; $i++) {
            $sertifikat = Sertifikat::create([
                'id_sertifikat'     => (string) Str::uuid(),
                'no_dokumen'        => 'DOC-' . strtoupper(Str::random(6)),
                'dokumen'           => 'file_sertifikat_' . $i . '.pdf',
                'jenis_sertifikat'  => 'Hak Pakai',
                'status_pengajuan'  => 'menunggu',
                'tanggal_pengajuan' => Carbon::now()->subDays(rand(1, 5))->toDateString(),
                'status'            => 'pending',
                'user_id'           => $user->id,
                'id_tanah'          => $tanahIds[array_rand($tanahIds)],
                'created_at'        => Carbon::now()->subDays(rand(1, 5)),
                'updated_at'        => Carbon::now()->subDays(rand(1, 5)),
            ]);

            $sertifikat->update([
                'status_pengajuan' => 'diterima',
                'status'           => 'approved',
            ]);
        }

        $this->command->info('7 tanah dan 7 sertifikat berhasil dibuat & di-update.');
    }
}
