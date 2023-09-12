<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFbDepositOrderAddressTable extends Migration
{
  public function up()
  {
    Schema::create('fb_deposit_order_address', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->bigInteger('deposit_order_id')->unsigned()->comment('Id of the "fb_deposit_order" table');
      $table->foreign('deposit_order_id')->references('id')->on('fb_deposit_order')->onDelete('cascade')->onUpdate('cascade');
      $table->bigInteger('address_id')->unsigned()->nullable(true)->comment('Id of the "fb_addresses" table');
      $table->foreign('address_id')->references('id')->on('fb_addresses')->onDelete('cascade')->onUpdate('cascade');
      $table->double('fee_amount')->comment('Fee amount of this action');
      $table->double('prev_amount')->comment('The amount before this action');
      $table->double('after_amount')->comment('The amount after this action');
      $table->double('net_amount')->comment('The amount affected by this action');
      $table->string('payment_status')->comment('Payment status of this transaction (e.g., pending, complete, over)');
      $table->string('description')->comment('Represents why this action happened (e.g., requested new address, deposit, withdrawal)');
      $table->string('related_address')->nullable(true)->comment('The address related to this address (e.g., source address for deposit)');
      $table->string('action_status')->comment('Status of this action, e.g., success, failed, etc.');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down()
  {
    Schema::dropIfExists('fb_deposit_order_address');
  }
}

