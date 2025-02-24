<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sertifikats', function (Blueprint $table) {
            $table->uuid('id_sertifikat')->primary();
            $table->uuid('id_tanah')->nullable();
            $table->string('noSertifikat')->unique();
            $table->string('namaWakif');
            $table->string('lokasi');
            $table->string('luasTanah');
            $table->string('fasilitas');
            $table->string('status');
            $table->string('dokBastw')->nullable(); // path dokumen PDF
            $table->string('dokAiw')->nullable();// path dokumen PDF
            $table->string('dokSw')->nullable(); // path dokumen PDF
            $table->timestamps();
            $table->foreign('id_tanah')->references('id_tanah')->on('tanahs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikats');
    }
};