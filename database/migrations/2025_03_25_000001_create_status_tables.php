<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Crear tabla para estados de solicitudes de servicio
        Schema::create('service_request_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Crear tabla para estados de compras
        Schema::create('purchase_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insertar estados iniciales para solicitudes de servicio
        DB::table('service_request_statuses')->insert([
            ['name' => 'pendiente', 'display_name' => 'Pendiente', 'description' => 'Solicitud pendiente de atención', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'en_proceso', 'display_name' => 'En Proceso', 'description' => 'Solicitud en proceso de atención', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'completado', 'display_name' => 'Completado', 'description' => 'Solicitud completada', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Insertar estados iniciales para compras
        DB::table('purchase_statuses')->insert([
            ['name' => 'pendiente', 'display_name' => 'Pendiente', 'description' => 'Compra pendiente de pago', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'pagado', 'display_name' => 'Pagado', 'description' => 'Compra pagada', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'entregado', 'display_name' => 'Entregado', 'description' => 'Compra entregada', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('service_request_statuses');
        Schema::dropIfExists('purchase_statuses');
    }
};
