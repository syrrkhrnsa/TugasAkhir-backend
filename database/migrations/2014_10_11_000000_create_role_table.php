<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateRoleTable extends Migration

{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Insert default roles
        DB::table('roles')->insert([
            ['id' => '326f0dde-2851-4e47-ac5a-de6923447317', 'name' => 'Pimpinan Jamaah'],
            ['id' => '3594bece-a684-4287-b0a2-7429199772a3', 'name' => 'Pimpinan Cabang'],
            ['id' => '26b2b64e-9ae3-4e2e-9063-590b1bb00480', 'name' => 'Bidgar Wakaf'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}