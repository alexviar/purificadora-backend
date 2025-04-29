<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Añadimos la columna comentarios_tecnico después de comentarios
            $table->text('comentarios_tecnico')->nullable()->after('comentarios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Eliminamos la columna si necesitamos revertir la migración
            $table->dropColumn('comentarios_tecnico');
        });
    }
};
