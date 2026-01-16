<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceApplicationsTable extends Migration
{
    public function up()
    {
        Schema::create('insurance_applications', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('marital_status');
            $table->string('email')->index();

            $table->text('residential_address');
            $table->unsignedSmallInteger('years_at_address');
            $table->text('previous_address')->nullable();

            $table->string('insurance_type');
            $table->string('carrier_name');
            $table->json('vehicle_vins');
            $table->date('insurance_expiration_date');

            $table->string('payment_method');
            $table->string('processing_officer_name');

            $table->string('valid_id_card_path');
            $table->string('previous_insurance_document_path');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('insurance_applications');
    }
}
