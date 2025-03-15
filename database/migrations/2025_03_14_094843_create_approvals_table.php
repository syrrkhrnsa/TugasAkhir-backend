<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up()
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // User yang mengajukan
            $table->uuid('approver_id')->nullable(); // User yang menyetujui
            $table->string('type'); // Jenis data yang disetujui, misalnya: tanah, sertifikat
            $table->uuid('data_id'); // ID data yang diminta untuk diubah
            $table->text('data'); // Data JSON sebelum perubahan
            $table->enum('status', ['ditinjau', 'disetujui', 'ditolak'])->default('ditinjau');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('approvals');
    }
};