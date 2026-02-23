<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthSupplementProductsTable extends Migration
{
    public function up()
    {
        Schema::create('health_supplement_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedTinyInteger('availability')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('health_supplement_products');
    }
}
