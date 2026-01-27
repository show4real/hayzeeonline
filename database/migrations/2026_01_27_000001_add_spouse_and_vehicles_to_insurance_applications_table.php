<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpouseAndVehiclesToInsuranceApplicationsTable extends Migration
{
    public function up()
    {
        Schema::table('insurance_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_applications', 'spouse_full_name')) {
                $table->string('spouse_full_name')->nullable()->after('marital_status');
            }

            if (! Schema::hasColumn('insurance_applications', 'spouse_dob')) {
                $table->date('spouse_dob')->nullable()->after('spouse_full_name');
            }

            if (! Schema::hasColumn('insurance_applications', 'spouse_drivers_license_number')) {
                $table->string('spouse_drivers_license_number')->nullable()->after('spouse_dob');
            }

            if (! Schema::hasColumn('insurance_applications', 'spouse_excluded_from_policy')) {
                $table->string('spouse_excluded_from_policy')->nullable()->after('spouse_drivers_license_number');
            }

            if (! Schema::hasColumn('insurance_applications', 'vehicles')) {
                $table->json('vehicles')->nullable()->after('carrier_name');
            }
        });
    }

    public function down()
    {
        Schema::table('insurance_applications', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_applications', 'vehicles')) {
                $table->dropColumn('vehicles');
            }

            if (Schema::hasColumn('insurance_applications', 'spouse_excluded_from_policy')) {
                $table->dropColumn('spouse_excluded_from_policy');
            }

            if (Schema::hasColumn('insurance_applications', 'spouse_drivers_license_number')) {
                $table->dropColumn('spouse_drivers_license_number');
            }

            if (Schema::hasColumn('insurance_applications', 'spouse_dob')) {
                $table->dropColumn('spouse_dob');
            }

            if (Schema::hasColumn('insurance_applications', 'spouse_full_name')) {
                $table->dropColumn('spouse_full_name');
            }
        });
    }
}
