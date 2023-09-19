<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailAndNameToFbDepositOrderTable extends Migration
{
  public function up()
  {
    Schema::table('fb_deposit_order', function (Blueprint $table) {
      $table->string('email', 255)->after('currency')->nullable();
      $table->string('name', 255)->after('email')->nullable();
    });
  }

  public function down()
  {
    Schema::table('fb_deposit_order', function (Blueprint $table) {
      $table->dropColumn(['email', 'name']);
    });
  }
}
