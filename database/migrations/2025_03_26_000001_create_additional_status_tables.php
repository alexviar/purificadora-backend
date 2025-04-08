<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Crear tabla para estados de detalles de compra
        Schema::create('purchase_detail_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Crear tabla para estados de alertas
        Schema::create('alert_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Crear tabla para estados de carritos
        Schema::create('cart_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insertar estados iniciales para detalles de compra
        DB::table('purchase_detail_statuses')->insert([
            ['name' => 'pendiente', 'display_name' => 'Pendiente', 'description' => 'Detalle pendiente de procesamiento', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'pagado', 'display_name' => 'Pagado', 'description' => 'Detalle pagado', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'entregado', 'display_name' => 'Entregado', 'description' => 'Detalle entregado', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Insertar estados iniciales para alertas
        DB::table('alert_statuses')->insert([
            ['name' => 'pendiente', 'display_name' => 'Pendiente', 'description' => 'Alerta pendiente de envío', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'enviada', 'display_name' => 'Enviada', 'description' => 'Alerta enviada', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'fallida', 'display_name' => 'Fallida', 'description' => 'Alerta fallida', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Insertar estados iniciales para carritos
        DB::table('cart_statuses')->insert([
            ['name' => 'activo', 'display_name' => 'Activo', 'description' => 'Carrito activo', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'abandonado', 'display_name' => 'Abandonado', 'description' => 'Carrito abandonado', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'completado', 'display_name' => 'Completado', 'description' => 'Carrito completado', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('purchase_detail_statuses');
        Schema::dropIfExists('alert_statuses');
        Schema::dropIfExists('cart_statuses');
    }
};
