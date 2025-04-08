<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('supplies', function (Blueprint $table) {
            // Primero eliminamos la columna existente
            $table->dropColumn('imagen');
        });

        // Luego añadimos la nueva columna como LONGBLOB
        // Usamos DB::statement porque Blueprint no tiene un método directo para LONGBLOB
        DB::statement('ALTER TABLE supplies ADD imagen LONGBLOB NULL');
    }

    public function down()
    {
        Schema::table('supplies', function (Blueprint $table) {
            // Primero eliminamos la columna LONGBLOB
            $table->dropColumn('imagen');

            // Luego restauramos la columna original como string
            $table->string('imagen')->nullable();
        });
    }
};
