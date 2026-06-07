<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGraphicsCardMemoryToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'graphics_card_memory')) {
                $table->string('graphics_card_memory')->nullable()->after('graphics_card');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'graphics_card_memory')) {
                $table->dropColumn('graphics_card_memory');
            }
        });
    }
}
