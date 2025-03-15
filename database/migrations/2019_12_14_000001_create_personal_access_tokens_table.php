<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalAccessTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('personal_access_tokens', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->uuid('tokenable_id'); // Ubah ini menjadi uuid
        $table->string('tokenable_type'); // Pastikan tokenable_type tetap menggunakan string
        $table->string('name');
        $table->string('token', 64)->unique();
        $table->text('abilities')->nullable();
        $table->timestamp('expires_at')->nullable()->after('token');
        $table->timestamp('last_used_at')->nullable()->after('expires_at');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personal_access_tokens');
    }
}