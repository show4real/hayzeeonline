<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'model')) {
                $table->string('model')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('products', 'subtype')) {
                $table->string('subtype')->nullable()->after('model');
            }

            if (! Schema::hasColumn('products', 'condition')) {
                $table->string('condition')->nullable()->after('subtype');
            }

            if (! Schema::hasColumn('products', 'number_of_cores')) {
                $table->string('number_of_cores')->nullable()->after('condition');
            }

            if (! Schema::hasColumn('products', 'storage_type')) {
                $table->string('storage_type')->nullable()->after('number_of_cores');
            }

            if (! Schema::hasColumn('products', 'display_size')) {
                $table->string('display_size')->nullable()->after('storage_type');
            }

            if (! Schema::hasColumn('products', 'graphics_card')) {
                $table->string('graphics_card')->nullable()->after('display_size');
            }

            if (! Schema::hasColumn('products', 'operating_system')) {
                $table->string('operating_system')->nullable()->after('graphics_card');
            }

            if (! Schema::hasColumn('products', 'color')) {
                $table->string('color')->nullable()->after('operating_system');
            }

            if (! Schema::hasColumn('products', 'exchange_possible')) {
                $table->string('exchange_possible')->nullable()->after('color');
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
            $columns = [
                'model',
                'subtype',
                'condition',
                'number_of_cores',
                'storage_type',
                'display_size',
                'graphics_card',
                'operating_system',
                'color',
                'exchange_possible',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
