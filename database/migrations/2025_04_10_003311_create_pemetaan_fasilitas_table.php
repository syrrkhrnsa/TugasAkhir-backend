<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pemetaan_fasilitas', function (Blueprint $table) {
            $table->uuid('id_pemetaan_fasilitas')->primary();
            $table->uuid('id_pemetaan_tanah');
            $table->uuid('id_user');
            $table->string('jenis_fasilitas'); // MASJID, SEKOLAH, PEMAKAMAN, dll
            $table->string('nama_fasilitas');
            $table->text('keterangan')->nullable();
            $table->string('jenis_geometri'); // POINT, POLYGON, LINESTRING, dll
            
            // Gunakan tipe geometry dengan SRID 4326 (WGS84) untuk konsistensi
            $table->geometry('geometri', 'GEOMETRY', 4326);
            
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('id_pemetaan_tanah')
                  ->references('id_pemetaan_tanah')
                  ->on('pemetaan_tanah')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_user')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

        // Tambahkan komentar untuk kolom
        DB::statement("COMMENT ON COLUMN pemetaan_fasilitas.jenis_fasilitas IS 'Jenis fasilitas: MASJID, SEKOLAH, PEMAKAMAN, dll'");
        DB::statement("COMMENT ON COLUMN pemetaan_fasilitas.jenis_geometri IS 'Tipe geometri: POINT, POLYGON, LINESTRING, dll'");
        DB::statement("COMMENT ON COLUMN pemetaan_fasilitas.geometri IS 'Menyimpan data geometri fasilitas dalam format PostGIS (WGS84)'");

        // Add spatial index dengan kondisi IF NOT EXISTS
        DB::statement('
            CREATE INDEX IF NOT EXISTS pemetaan_fasilitas_geometri_index 
            ON pemetaan_fasilitas 
            USING GIST(geometri)
        ');

        // Index untuk pencarian berdasarkan jenis fasilitas
        DB::statement('
            CREATE INDEX IF NOT EXISTS pemetaan_fasilitas_jenis_index 
            ON pemetaan_fasilitas (jenis_fasilitas)
        ');
    }

    public function down()
    {
        // Hapus indeks terlebih dahulu
        DB::statement('DROP INDEX IF EXISTS pemetaan_fasilitas_geometri_index');
        DB::statement('DROP INDEX IF EXISTS pemetaan_fasilitas_jenis_index');
        
        // Baru hapus tabel
        Schema::dropIfExists('pemetaan_fasilitas');
    }
};