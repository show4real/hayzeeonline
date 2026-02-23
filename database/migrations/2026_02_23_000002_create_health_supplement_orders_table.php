<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthSupplementOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('health_supplement_orders', function (Blueprint $table) {
            $table->id();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('customer_address')->nullable();

            $table->unsignedInteger('total_price')->default(0);
            $table->unsignedTinyInteger('status')->default(0);

            $table->string('payment_reference')->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('payment_status')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('health_supplement_orders');
    }
}
