<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHotelRegistration extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('user_profiles', function (Blueprint $table) {
      $table->string('hotel_id', 20)->index()->nullable()->after('hotel_name');
      $table->string('hotel_ctx', 20)->nullable()->after('hotel_id');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('user_profiles', function (Blueprint $table) {
      $table->dropColumn(['hotel_id', 'hotel_ctx']);
    });
  }
}
