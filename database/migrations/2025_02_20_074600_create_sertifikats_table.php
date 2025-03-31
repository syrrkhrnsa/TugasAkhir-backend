<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sertifikats', function (Blueprint $table) {
            $table->uuid('id_sertifikat')->primary();
            $table->string('no_dokumen')->nullable()->unique();
            $table->string('dokumen')->nullable();
            $table->string('jenis_sertifikat')->nullable();
            $table->string('status_pengajuan')->nullable();
            $table->date('tanggal_pengajuan');
            $table->string('status');
            $table->uuid('user_id');
            $table->uuid('id_tanah');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_tanah')->references('id_tanah')->on('tanahs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikats');
    }
};