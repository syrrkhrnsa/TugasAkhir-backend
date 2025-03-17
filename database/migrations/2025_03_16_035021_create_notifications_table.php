<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID sebagai primary key
            $table->string('type'); // Tipe notifikasi
            $table->uuid('notifiable_id'); // Ubah ke UUID
            $table->string('notifiable_type'); // Tipe model yang terkait
            $table->text('data'); // Data notifikasi
            $table->timestamp('read_at')->nullable(); // Waktu notifikasi dibaca
            $table->timestamps(); // created_at dan updated_at

            // Index untuk kolom notifiable
            $table->index(['notifiable_id', 'notifiable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};