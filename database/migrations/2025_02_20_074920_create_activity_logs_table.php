<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('action'); // create, update, delete
            $table->string('model_type'); // Nama Model (ex: App\Models\Sertifikat)
            $table->uuid('model_id'); // ID dari Model (ex: id_sertifikat atau id_tanah)
            $table->json('changes')->nullable(); // Data perubahan
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};