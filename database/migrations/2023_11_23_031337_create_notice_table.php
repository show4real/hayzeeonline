<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('notice');
            $table->timestamps();
        });

        $this->importNotice();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notices');
    }

    private function importNotice() {


        $notice = [
            [
                'notice' => 'The Exchange Rate volatility has indeed Negatively impact the fluctuation of stock market Price. Which in turn render all price on our different Platform invalid. Please kindly ask for current price before making a final pick. Also of Note is that, the price giving at an instant in time is only valid for that time. THANK YOU Signed Hayzee Computer Resources',
                'created_at' => now(),
                'updated_at' => now()
            ],
          

        ];

        \Illuminate\Support\Facades\DB::table('notices')->insert($notice);
    }
}
