<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTxidToFbDepositOrderAddress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('fb_deposit_order_address', function (Blueprint $table) {
        $table->string('txid', 255)->nullable(); // Add the txid field
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('fb_deposit_order_address', function (Blueprint $table) {
        $table->dropColumn('txid'); // Remove the txid field if needed
      });
    }
}
