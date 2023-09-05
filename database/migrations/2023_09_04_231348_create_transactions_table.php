<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('transactions', function (Blueprint $table) {
      $table->string('orderNo', 255)->primary(); // Change the primary key to varchar(255)
      $table->bigInteger('partner_id')->unsigned();
      $table->foreign('partner_id')
        ->references('id')
        ->on('partners')
        ->onDelete('cascade')
        ->onUpdate('cascade');
      $table->string('email_address', 255)->nullable(true);
      $table->string('card_holder_name', 255)->nullable(true);
      $table->float('amount');
      $table->float('fee');
      $table->string('product_name', 255);
      $table->string('status', 255);
      $table->string('partner_domain', 255);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('transactions');
  }
}


