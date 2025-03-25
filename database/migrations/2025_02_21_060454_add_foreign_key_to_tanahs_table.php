<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        // Menambahkan foreign key pada sertifikats
        Schema::table('sertifikats', function (Blueprint $table) {
            $table->uuid('id_tanah')->nullable()->after('id_sertifikat');
            $table->foreign('id_tanah')->references('id_tanah')->on('tanahs')->onDelete('set null');
        });
    }

    public function down(): void
    {

        Schema::table('sertifikats', function (Blueprint $table) {
            $table->dropForeign(['id_tanah']);
            $table->dropColumn('id_tanah');
        });
    }
};