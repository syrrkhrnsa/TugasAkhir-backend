<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Membuat tabel tanah
        Schema::create('tanahs', function (Blueprint $table) {
            $table->uuid('id_tanah')->primary();
            $table->string('NamaPimpinanJamaah');
            $table->string('NamaWakif');
            $table->string('lokasi');
            $table->string('luasTanah');
            $table->string('legalitas');
            $table->string('status');
            $table->uuid('user_id');
            
            // Kolom tambahan
            $table->string('jenis_tanah')->nullable();
            $table->string('batas_timur')->nullable();
            $table->string('batas_selatan')->nullable();
            $table->string('batas_barat')->nullable();
            $table->string('batas_utara')->nullable();
            $table->string('panjang_tanah')->nullable();
            $table->string('lebar_tanah')->nullable();
            $table->text('catatan')->nullable();
            $table->text('alamat_wakif')->nullable();
            
            // Kolom geospatial
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Tambahkan kolom geometry secara terpisah untuk PostGIS
        DB::statement('ALTER TABLE tanahs ADD COLUMN koordinat geometry(Point,4326)');
        
        // Tambahkan indeks spasial
        DB::statement('CREATE INDEX idx_tanahs_koordinat ON tanahs USING GIST(koordinat)');
        
        // Komentar untuk dokumentasi
        DB::statement("COMMENT ON COLUMN tanahs.koordinat IS 'Menyimpan titik koordinat tanah dalam format PostGIS (WGS84)'");
    }

    public function down()
    {
        // Hapus indeks terlebih dahulu
        DB::statement('DROP INDEX IF EXISTS idx_tanahs_koordinat');
        
        // Baru hapus tabel
        Schema::dropIfExists('tanahs');
    }
};