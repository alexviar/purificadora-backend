<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentConfigurationsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_configurations', function (Blueprint $table) {
            $table->id();
            $table->boolean('stripe_enabled')->default(false);
            $table->boolean('efectivo_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_configurations');
    }
}
