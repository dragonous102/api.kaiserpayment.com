<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('partners', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('name', 255);
        $table->string('domain', 255);
        $table->float('fee');
        $table->tinyInteger('status');
        $table->timestamps();
        $table->softDeletes(); // Add soft deletes
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::dropIfExists('partners');
    }
}
