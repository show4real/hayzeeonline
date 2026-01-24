<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientConsentsTable extends Migration
{
    public function up()
    {
        Schema::create('client_consents', function (Blueprint $table) {
            $table->id();

            $table->string('consent_client_full_name');
            $table->string('consent_client_phone');
            $table->string('consent_client_email')->index();
            $table->text('consent_client_address');

            $table->string('fixed_fee_amount');
            $table->boolean('agency_fee_consent');
            $table->string('agency_fee_payment_method')->nullable();
            $table->string('agency_fee_amount_paid')->nullable();

            $table->string('client_consent_signature_type');
            $table->string('client_consent_signed_by_last_name');
            $table->dateTime('client_consent_signed_at');

            $table->string('client_consent_signature_file_path')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_consents');
    }
}
