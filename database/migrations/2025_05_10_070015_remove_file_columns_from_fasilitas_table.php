<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fasilitas', function (Blueprint $table) {
            $table->dropColumn(['file_360', 'file_gambar', 'file_pdf']);
        });
    }

    public function down()
    {
        Schema::table('fasilitas', function (Blueprint $table) {
            $table->string('file_360')->nullable();
            $table->string('file_gambar')->nullable();
            $table->string('file_pdf')->nullable();
        });
    }
};