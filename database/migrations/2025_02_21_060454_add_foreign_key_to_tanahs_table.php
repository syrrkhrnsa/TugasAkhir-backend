<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tanahs', function (Blueprint $table) {
            $table->foreign('id_sertifikat')->references('id_sertifikat')->on('sertifikats')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tanahs', function (Blueprint $table) {
            $table->dropForeign(['id_sertifikat']);
        });
    }
};