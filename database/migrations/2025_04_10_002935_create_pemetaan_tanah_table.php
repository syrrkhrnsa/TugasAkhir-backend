<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {

        Schema::create('pemetaan_tanah', function (Blueprint $table) {
            $table->uuid('id_pemetaan_tanah')->primary();
            $table->uuid('id_tanah');
            $table->uuid('id_user');
            $table->string('nama_pemetaan');
            $table->text('keterangan')->nullable();
            $table->string('jenis_geometri'); // POLYGON, MULTIPOLYGON, dll
            
            // Gunakan tipe geography untuk akurasi yang lebih baik dalam perhitungan jarak/luas
            // atau geometry untuk performa yang lebih cepat
            $table->geometry('geometri', 'GEOMETRY', 4326); // SRID 4326 (WGS84)
            
            $table->timestamps();

            $table->foreign('id_tanah')->references('id_tanah')->on('tanahs')->onDelete('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });

        // Tambahkan komentar untuk kolom geometri
        DB::statement("COMMENT ON COLUMN pemetaan_tanah.geometri IS 'Menyimpan data geometri tanah dalam format PostGIS (WGS84)'");
        
        // Add spatial index dengan kondisi khusus
        DB::statement('
            CREATE INDEX IF NOT EXISTS pemetaan_tanah_geometri_index 
            ON pemetaan_tanah 
            USING GIST(geometri)
        ');
    }

    public function down()
    {
        // Hapus indeks terlebih dahulu
        DB::statement('DROP INDEX IF EXISTS pemetaan_tanah_geometri_index');
        
        // Baru hapus tabel
        Schema::dropIfExists('pemetaan_tanah');
        
        // Opsional: Hapus ekstensi jika ingin membersihkan sepenuhnya
        // DB::statement('DROP EXTENSION IF EXISTS postgis');
    }
};