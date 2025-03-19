<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sertifikats', function (Blueprint $table) {
            $table->uuid('id_sertifikat')->primary();
            $table->string('noDokumenBastw')->nullable()->unique(); // Nomor dokumen BASTW
            $table->string('noDokumenAIW')->nullable()->unique(); // Nomor dokumen AIW
            $table->string('noDokumenSW')->nullable()->unique(); // Nomor dokumen SW
            $table->string('status');
            $table->string('legalitas');
            $table->uuid('user_id');
            $table->string('dokBastw')->nullable();
            $table->string('dokAiw')->nullable();
            $table->string('dokSw')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikats');
    }
};