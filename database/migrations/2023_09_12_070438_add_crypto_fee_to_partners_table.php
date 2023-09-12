<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCryptoFeeToPartnersTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('partners', function (Blueprint $table) {
      $table->float('crypto_fee')->comment('The fee value of crypto');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('partners', function (Blueprint $table) {
      $table->dropColumn('crypto_fee');
    });
  }
}
