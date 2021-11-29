<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesAndRoomImagesTables extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('images', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id')->index();
      $table->string('hotel_id', 20)->index(); // for better access to root folder, without loading user.profile relation
      $table->string('code', 16)->index();
      $table->boolean('lowres')->default(false);
      $table->unsignedMediumInteger('width');
      $table->unsignedMediumInteger('height');
      $table->unsignedMediumInteger('size');
      $table->unsignedMediumInteger('orig_width');
      $table->unsignedMediumInteger('orig_height');
      $table->unsignedMediumInteger('orig_size');
      $table->string('name', 300)->nullable();
      $table->timestamps();
    });

    Schema::create('room_images', function (Blueprint $table) {
      $table->id();
      $table->string('room_id', 20);
      $table->unsignedBigInteger('image_id');
      $table->unsignedSmallInteger('display_order');

      $table->foreign('image_id')->references('id')->on('images')->cascadeOnDelete();
      $table->unique(['room_id', 'image_id']);
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('room_images');
    Schema::dropIfExists('images');
  }
}
