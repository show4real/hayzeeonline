<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhoneToInsuranceApplicationsTable extends Migration
{
    public function up()
    {
        Schema::table('insurance_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_applications', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
        });
    }

    public function down()
    {
        Schema::table('insurance_applications', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_applications', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
}
