<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserImagesTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('user_images', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id')->index();
      $table->string('code', 20)->index();
      $table->unsignedMediumInteger('width');
      $table->unsignedMediumInteger('height');
      $table->unsignedMediumInteger('size');
      $table->unsignedMediumInteger('orig_width');
      $table->unsignedMediumInteger('orig_height');
      $table->unsignedMediumInteger('orig_size');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('user_images');
  }
}
