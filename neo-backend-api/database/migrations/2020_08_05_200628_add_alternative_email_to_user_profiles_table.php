<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAlternativeEmailToUserProfilesTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('user_profiles', function (Blueprint $table) {
      $table->string('alternative_email')->after('last_name')->nullable();
      $table->string('website')->after('hotel_ctx')->nullable();
      $table->mediumInteger('currency')->after('hotel_ctx')->unsigned()->nullable();
      $table->integer('capacity')->after('hotel_ctx')->nullable();
      $table->mediumInteger('capacity_mode')->after('hotel_ctx')->unsigned()->nullable();
      $table->string('federal_state')->after('hotel_ctx')->nullable();
      $table->decimal('longtitude', 12, 8)->after('street')->nullable();
      $table->decimal('latitude', 12, 8)->after('street')->nullable();
      $table->string('street_optional')->after('street')->nullable();
      //
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
      //
      $table->dropColumn([
        'alternative_email', 'website', 'currency', 'capacity',
        'capacity_mode', 'federal_state', 'longtitude', 'latitude', 'street_optional',
      ]);
    });
  }
}
