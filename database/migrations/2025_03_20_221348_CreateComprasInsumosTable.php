<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('supply_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['pendiente', 'pagado', 'entregado']);
            $table->decimal('precio_total', 10, 2);
            $table->enum('metodo_pago', ['stripe', 'efectivo']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supply_purchases');
    }
};
