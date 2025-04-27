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
            $table->enum('jenis_fasilitas', ['Bergerak', 'Tidak Bergerak']); // ENUM with only Bergerak and Tidak Bergerak
            $table->string('kategori_fasilitas'); // New field for category
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

        // Add comments for columns
        DB::statement("COMMENT ON COLUMN pemetaan_fasilitas.jenis_fasilitas IS 'Jenis fasilitas: Bergerak, Tidak Bergerak'");
        DB::statement("COMMENT ON COLUMN pemetaan_fasilitas.kategori_fasilitas IS 'Kategori fasilitas, seperti umum atau khusus'");
        DB::statement("COMMENT ON COLUMN pemetaan_fasilitas.jenis_geometri IS 'Tipe geometri: POINT, POLYGON, LINESTRING, dll'");
        DB::statement("COMMENT ON COLUMN pemetaan_fasilitas.geometri IS 'Menyimpan data geometri fasilitas dalam format PostGIS (WGS84)'");

        // Add spatial index for geometry column
        DB::statement('
            CREATE INDEX IF NOT EXISTS pemetaan_fasilitas_geometri_index 
            ON pemetaan_fasilitas 
            USING GIST(geometri)
        ');

        // Index for searching by jenis_fasilitas
        DB::statement('
            CREATE INDEX IF NOT EXISTS pemetaan_fasilitas_jenis_index 
            ON pemetaan_fasilitas (jenis_fasilitas)
        ');
    }

    public function down()
    {
        // Drop indices first
        DB::statement('DROP INDEX IF EXISTS pemetaan_fasilitas_geometri_index');
        DB::statement('DROP INDEX IF EXISTS pemetaan_fasilitas_jenis_index');
        
        // Then drop the table
        Schema::dropIfExists('pemetaan_fasilitas');
    }
};
