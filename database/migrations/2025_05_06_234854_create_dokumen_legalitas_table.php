<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dokumen_legalitas', function (Blueprint $table) {
            $table->uuid('id_dokumen_legalitas')->primary();
            $table->uuid('id_sertifikat');
            $table->string('dokumen_legalitas');
            $table->timestamps();

            $table->foreign('id_sertifikat')
                  ->references('id_sertifikat')
                  ->on('sertifikats')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_legalitas');
    }
};