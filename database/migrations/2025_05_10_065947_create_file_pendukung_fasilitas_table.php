<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('file_pendukung_fasilitas', function (Blueprint $table) {
            $table->uuid('id_file_pendukung')->primary();
            $table->uuid('id_fasilitas');
            $table->enum('jenis_file', ['360', 'gambar', 'dokumen']); // Sesuai kebutuhan FE
            $table->boolean('is_primary')->default(false); // Untuk gambar utama
            $table->string('path_file');
            $table->string('nama_asli');
            $table->string('mime_type');
            $table->integer('ukuran_file'); // dalam bytes
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('id_fasilitas')
                  ->references('id_fasilitas')
                  ->on('fasilitas')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_pendukung_fasilitas');
    }
};