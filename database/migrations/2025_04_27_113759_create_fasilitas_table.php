<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fasilitas', function (Blueprint $table) {
            $table->uuid('id_fasilitas')->primary();
            $table->uuid('id_pemetaan_fasilitas');
            $table->uuid('id_tanah');
            $table->string('file_360')->nullable();
            $table->string('file_gambar')->nullable();
            $table->string('file_pdf')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_pemetaan_fasilitas')
                ->references('id_pemetaan_fasilitas')
                ->on('pemetaan_fasilitas')
                ->onDelete('cascade');

            $table->foreign('id_tanah')
                ->references('id_tanah')
                ->on('tanahs')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasilitas');
    }
};