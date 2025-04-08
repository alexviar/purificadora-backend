<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planta_id');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('tecnico_id')->nullable();
            $table->enum('estado', ['pendiente', 'en_proceso', 'completado']);
            $table->text('comentarios')->nullable();
            $table->json('fotos_antes')->nullable();
            $table->json('fotos_despues')->nullable();
            $table->timestamps();

            $table->foreign('planta_id')->references('id')->on('plants')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tecnico_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_requests');
    }
};
