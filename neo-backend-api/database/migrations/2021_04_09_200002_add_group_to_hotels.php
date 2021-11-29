<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupToHotels extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('hotels', function (Blueprint $table) {
      $table->unsignedBigInteger('group_id')->nullable()->after('user_id');
      $table->foreign('group_id')->references('id')->on('hotel_groups')->nullOnDelete();
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
      $table->dropConstrainedForeignId('group_id');
    });
  }
}
