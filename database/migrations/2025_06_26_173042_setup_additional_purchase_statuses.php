<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('purchase_statuses')->insertOrIgnore([
            ['id' => 4, 'name' => 'pago pendiente', 'display_name' => 'Pago pendiente', 'description' => 'El pago está pendiente'],
            ['id' => 5, 'name' => 'Cancelado', 'display_name' => 'Cancelado', 'description' => 'La compra ha sido cancelada'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('purchase_statuses')->whereIn('id', [4, 5])->delete();
    }
};
