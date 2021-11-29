<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgentToHotelsTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('hotels', function (Blueprint $table) {
      $table->unsignedBigInteger('user_id')->nullable()->change();
      $table->unsignedBigInteger('agent_id')->nullable()->after('group_id')->index();
    });
    Schema::table('hotel_images', function (Blueprint $table) {
      $table->unsignedBigInteger('user_id')->nullable()->change();
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
      $table->unsignedBigInteger('user_id')->change();
      $table->dropColumn('agent_id');
    });
    Schema::table('hotel_images', function (Blueprint $table) {
      $table->unsignedBigInteger('user_id')->change();
    });
  }
}
