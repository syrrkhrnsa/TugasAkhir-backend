<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventaris', function (Blueprint $table) {
            $table->uuid('id_inventaris')->primary();
            $table->uuid('id_fasilitas');
            $table->string('nama_barang');
            $table->string('kode_barang')->nullable();
            $table->string('satuan');
            $table->integer('jumlah')->default(1);
            $table->text('detail')->nullable();
            $table->text('deskripsi')->nullable();
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'])->default('baik');
            $table->text('catatan')->nullable();
            $table->timestamps();

            // Foreign Key
            $table->foreign('id_fasilitas')
                ->references('id_fasilitas')
                ->on('fasilitas')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris');
    }
};