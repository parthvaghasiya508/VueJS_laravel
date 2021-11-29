<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMimeToHotelImages extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::table('hotel_images', function (Blueprint $table) {
        $table->string('mime')->after('orig_size')->default('image/jpeg');
      });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::table('hotel_images', function (Blueprint $table) {
        $table->dropColumn('mime');
      });
  }
}
