<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelGroupImagesTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('hotel_group_images', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id')->index();
      $table->unsignedBigInteger('group_id');
      $table->foreign('group_id')->references('id')->on('hotel_groups');
      $table->string('code', 20)->index();
      $table->boolean('resized')->default(false);
      $table->unsignedMediumInteger('width');
      $table->unsignedMediumInteger('height');
      $table->unsignedMediumInteger('size');
      $table->unsignedMediumInteger('orig_width');
      $table->unsignedMediumInteger('orig_height');
      $table->unsignedMediumInteger('orig_size');
      $table->timestamps();
    });

    Schema::table('hotel_groups', function (Blueprint $table) {
      $table->dropColumn('logo');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('hotel_group_images');
    Schema::table('hotel_groups', function (Blueprint $table) {
      $table->string("logo")->nullable()->after('name');
    });
  }
}
