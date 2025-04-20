<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tanahs', function (Blueprint $table) {
            $table->uuid('id_tanah')->primary();
            $table->string('NamaPimpinanJamaah');
            $table->string('NamaWakif');
            $table->string('lokasi');
            $table->string('luasTanah');
            $table->string('legalitas');
            $table->string('status');
            $table->uuid('user_id');

            $table->string('jenis_tanah')->nullable();
            $table->string('batas_timur')->nullable();
            $table->string('batas_selatan')->nullable();
            $table->string('batas_barat')->nullable();
            $table->string('batas_utara')->nullable();
            $table->string('panjang_tanah')->nullable();
            $table->string('lebar_tanah')->nullable();
            $table->text('catatan')->nullable();
            $table->text('alamat_wakif')->nullable();
            
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanahs');
    }
};