<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Primero, obtener los IDs de los estados para hacer la migración de datos
        $serviceStatuses = DB::table('service_request_statuses')
            ->pluck('id', 'name')
            ->toArray();

        $purchaseStatuses = DB::table('purchase_statuses')
            ->pluck('id', 'name')
            ->toArray();

        // Modificar la tabla de solicitudes de servicio
        Schema::table('service_requests', function (Blueprint $table) {
            // Crear nueva columna para la referencia al estado
            $table->unsignedBigInteger('status_id')->nullable()->after('tecnico_id');

            // Crear la relación con la tabla de estados
            $table->foreign('status_id')->references('id')->on('service_request_statuses');
        });

        // Migrar los datos de estado enum a la nueva columna status_id
        DB::table('service_requests')->get()->each(function ($request) use ($serviceStatuses) {
            DB::table('service_requests')
                ->where('id', $request->id)
                ->update(['status_id' => $serviceStatuses[$request->estado] ?? null]);
        });

        // Modificar la tabla de compras de insumos
        Schema::table('supply_purchases', function (Blueprint $table) {
            // Crear nueva columna para la referencia al estado
            $table->unsignedBigInteger('status_id')->nullable()->after('cliente_id');

            // Crear la relación con la tabla de estados
            $table->foreign('status_id')->references('id')->on('purchase_statuses');
        });

        // Migrar los datos de estado enum a la nueva columna status_id
        DB::table('supply_purchases')->get()->each(function ($purchase) use ($purchaseStatuses) {
            DB::table('supply_purchases')
                ->where('id', $purchase->id)
                ->update(['status_id' => $purchaseStatuses[$purchase->estado] ?? null]);
        });

        // Eliminar las columnas enum originales después de migrar los datos
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        Schema::table('supply_purchases', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

    public function down()
    {
        // Restaurar las columnas enum originales
        Schema::table('service_requests', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'en_proceso', 'completado'])->nullable()->after('tecnico_id');
        });

        Schema::table('supply_purchases', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'pagado', 'entregado'])->nullable()->after('cliente_id');
        });

        // Migrar los datos de vuelta a las columnas enum
        $serviceStatuses = DB::table('service_request_statuses')
            ->pluck('name', 'id')
            ->toArray();

        $purchaseStatuses = DB::table('purchase_statuses')
            ->pluck('name', 'id')
            ->toArray();

        DB::table('service_requests')->get()->each(function ($request) use ($serviceStatuses) {
            if (isset($request->status_id) && isset($serviceStatuses[$request->status_id])) {
                DB::table('service_requests')
                    ->where('id', $request->id)
                    ->update(['estado' => $serviceStatuses[$request->status_id]]);
            }
        });

        DB::table('supply_purchases')->get()->each(function ($purchase) use ($purchaseStatuses) {
            if (isset($purchase->status_id) && isset($purchaseStatuses[$purchase->status_id])) {
                DB::table('supply_purchases')
                    ->where('id', $purchase->id)
                    ->update(['estado' => $purchaseStatuses[$purchase->status_id]]);
            }
        });

        // Eliminar las columnas de referencia a estados
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });

        Schema::table('supply_purchases', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
