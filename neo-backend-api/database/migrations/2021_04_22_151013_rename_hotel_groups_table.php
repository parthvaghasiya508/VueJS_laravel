<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameHotelGroupsTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('hotel_groups', function (Blueprint $table) {
      $table->rename('groups');
    });
    Schema::table('hotel_group_images', function (Blueprint $table) {
      $table->rename('group_images');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('groups', function (Blueprint $table) {
      $table->rename('hotel_groups');
    });
    Schema::table('group_images', function (Blueprint $table) {
      $table->rename('hotel_group_images');
    });
  }
}
