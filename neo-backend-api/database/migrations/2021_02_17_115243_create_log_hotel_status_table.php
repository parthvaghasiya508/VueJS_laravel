<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogHotelStatusTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('log_hotel_status', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id')->index()->nullable();
      $table->unsignedBigInteger('hotel_id')->index();
      $table->boolean('status');
      $table->timestamps();

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
    Schema::dropIfExists('log_hotel_status');
  }
}
