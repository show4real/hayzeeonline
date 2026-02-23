<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthSupplementOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('health_supplement_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('health_supplement_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('health_supplement_products')->onDelete('restrict');

            $table->index(['order_id']);
            $table->index(['product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('health_supplement_order_items');
    }
}
