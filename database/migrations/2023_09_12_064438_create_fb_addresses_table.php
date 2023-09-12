<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFbAddressesTable extends Migration
{
  public function up()
  {
    Schema::create('fb_addresses', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->string('address')->comment('The address to be deposited');
      $table->string('legacy_address')->comment('The legacy address to be deposited');
      $table->string('asset_id')->comment('Id of the assets in Fireblocks, e.g., BTC, USDT, etc.');
      $table->string('vault_account_id')->comment('Id of the vault account in Fireblocks');
      $table->string('vault_account_name')->comment('Name of the vault account in Fireblocks');
      $table->tinyInteger('active')->default(1)->comment('The flag representing the active status of this address (1: active, 0: inactive)');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down()
  {
    Schema::dropIfExists('fb_addresses');
  }
}

