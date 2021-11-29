<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoomdbIsMasterToHotelsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::table('hotels', function (Blueprint $table) {
          $table->boolean('roomdb_is_master')->default(false);
      });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::table('hotels', function (Blueprint $table) {
          $table->dropColumn('roomdb_is_master');
      });
  }
}
