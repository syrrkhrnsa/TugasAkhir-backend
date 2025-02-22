<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tanahs', function (Blueprint $table) {
            $table->uuid('id_tanah')->primary();
            $table->string('NamaTanah');
            $table->string('NamaWakif');
            $table->string('lokasi');
            $table->string('luasTanah');
            $table->uuid('id_sertifikat')->nullable();
            $table->timestamps();
            $table->geometry('koordinatTanah')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanahs');
    }
};