<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentUserHotelTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('agent_user_hotel', function (Blueprint $table) {
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('hotel_id');
      $table->primary(['user_id', 'hotel_id']);
      $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
      $table->foreign('hotel_id')->references('id')->on('hotels')->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('agent_user_hotel');
  }
}
