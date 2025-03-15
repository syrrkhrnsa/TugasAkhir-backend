<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Menambahkan foreign key pada tanahs
        Schema::table('tanahs', function (Blueprint $table) {
            $table->uuid('id_sertifikat')->nullable()->after('status');
            $table->foreign('id_sertifikat')->references('id_sertifikat')->on('sertifikats')->onDelete('set null');
        });

        // Menambahkan foreign key pada sertifikats
        Schema::table('sertifikats', function (Blueprint $table) {
            $table->uuid('id_tanah')->nullable()->after('id_sertifikat');
            $table->foreign('id_tanah')->references('id_tanah')->on('tanahs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tanahs', function (Blueprint $table) {
            $table->dropForeign(['id_sertifikat']);
            $table->dropColumn('id_sertifikat');
        });

        Schema::table('sertifikats', function (Blueprint $table) {
            $table->dropForeign(['id_tanah']);
            $table->dropColumn('id_tanah');
        });
    }
};