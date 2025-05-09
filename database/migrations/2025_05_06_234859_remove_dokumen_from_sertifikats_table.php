<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sertifikats', function (Blueprint $table) {
            $table->dropColumn('dokumen');
        });
    }

    public function down(): void
    {
        Schema::table('sertifikats', function (Blueprint $table) {
            $table->string('dokumen')->nullable();
        });
    }
};