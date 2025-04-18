<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('supplies')) {
            Schema::create('supplies', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->text('descripcion');
                $table->decimal('precio', 10, 2);
                $table->integer('stock');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });

            // Ahora modificamos la columna 'imagen' para que sea LONGBLOB
            DB::statement("ALTER TABLE supplies ADD imagen LONGBLOB NULL");
        }
    }

    public function down()
    {
        Schema::dropIfExists('supplies');
    }
};
