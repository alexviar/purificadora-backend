<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('supply_purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_insumo_id')->constrained('supply_purchases')->onDelete('cascade');
            $table->enum('estado', ['pendiente', 'pagado', 'entregado'])->default('pendiente');
            $table->string('tipo'); // 'plant' o 'supply'
            $table->unsignedBigInteger('item_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supply_purchase_details');
    }
};
