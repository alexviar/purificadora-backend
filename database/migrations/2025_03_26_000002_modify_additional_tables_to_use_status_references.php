<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Obtener los IDs de los estados para hacer la migración de datos
        $purchaseDetailStatuses = DB::table('purchase_detail_statuses')
            ->pluck('id', 'name')
            ->toArray();

        $alertStatuses = DB::table('alert_statuses')
            ->pluck('id', 'name')
            ->toArray();

        $cartStatuses = DB::table('cart_statuses')
            ->pluck('id', 'name')
            ->toArray();

        // Modificar la tabla de detalles de compra de insumos
        Schema::table('supply_purchase_details', function (Blueprint $table) {
            // Crear nueva columna para la referencia al estado
            $table->unsignedBigInteger('status_id')->nullable()->after('compra_insumo_id');

            // Crear la relación con la tabla de estados
            $table->foreign('status_id')->references('id')->on('purchase_detail_statuses');
        });

        // Migrar los datos de estado enum a la nueva columna status_id
        DB::table('supply_purchase_details')->get()->each(function ($detail) use ($purchaseDetailStatuses) {
            DB::table('supply_purchase_details')
                ->where('id', $detail->id)
                ->update(['status_id' => $purchaseDetailStatuses[$detail->estado] ?? null]);
        });

        // Modificar la tabla de alertas
        Schema::table('alerts', function (Blueprint $table) {
            // Crear nueva columna para la referencia al estado
            $table->unsignedBigInteger('status_id')->nullable()->after('user_id');

            // Crear la relación con la tabla de estados
            $table->foreign('status_id')->references('id')->on('alert_statuses');
        });

        // Migrar los datos de estado enum a la nueva columna status_id
        DB::table('alerts')->get()->each(function ($alert) use ($alertStatuses) {
            DB::table('alerts')
                ->where('id', $alert->id)
                ->update(['status_id' => $alertStatuses[$alert->estado] ?? null]);
        });

        // Modificar la tabla de carritos
        Schema::table('carts', function (Blueprint $table) {
            // Crear nueva columna para la referencia al estado
            $table->unsignedBigInteger('status_id')->nullable()->after('user_id');

            // Crear la relación con la tabla de estados
            $table->foreign('status_id')->references('id')->on('cart_statuses');
        });

        // Migrar los datos de estado enum a la nueva columna status_id
        DB::table('carts')->get()->each(function ($cart) use ($cartStatuses) {
            DB::table('carts')
                ->where('id', $cart->id)
                ->update(['status_id' => $cartStatuses[$cart->estado] ?? null]);
        });

        // Eliminar las columnas enum originales después de migrar los datos
        Schema::table('supply_purchase_details', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn('estado');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

    public function down()
    {
        // Restaurar las columnas enum originales
        Schema::table('supply_purchase_details', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'pagado', 'entregado'])->nullable()->after('compra_insumo_id');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'enviada', 'fallida'])->nullable()->after('user_id');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'abandonado', 'completado'])->nullable()->after('user_id');
        });

        // Migrar los datos de vuelta a las columnas enum
        $purchaseDetailStatuses = DB::table('purchase_detail_statuses')
            ->pluck('name', 'id')
            ->toArray();

        $alertStatuses = DB::table('alert_statuses')
            ->pluck('name', 'id')
            ->toArray();

        $cartStatuses = DB::table('cart_statuses')
            ->pluck('name', 'id')
            ->toArray();

        DB::table('supply_purchase_details')->get()->each(function ($detail) use ($purchaseDetailStatuses) {
            if (isset($detail->status_id) && isset($purchaseDetailStatuses[$detail->status_id])) {
                DB::table('supply_purchase_details')
                    ->where('id', $detail->id)
                    ->update(['estado' => $purchaseDetailStatuses[$detail->status_id]]);
            }
        });

        DB::table('alerts')->get()->each(function ($alert) use ($alertStatuses) {
            if (isset($alert->status_id) && isset($alertStatuses[$alert->status_id])) {
                DB::table('alerts')
                    ->where('id', $alert->id)
                    ->update(['estado' => $alertStatuses[$alert->status_id]]);
            }
        });

        DB::table('carts')->get()->each(function ($cart) use ($cartStatuses) {
            if (isset($cart->status_id) && isset($cartStatuses[$cart->status_id])) {
                DB::table('carts')
                    ->where('id', $cart->id)
                    ->update(['estado' => $cartStatuses[$cart->status_id]]);
            }
        });

        // Eliminar las columnas de referencia a estados
        Schema::table('supply_purchase_details', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
