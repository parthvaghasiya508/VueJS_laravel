<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormatsAndLanguagesToUserProfilesTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::table('user_profiles', function (Blueprint $table) {
          $table->string('date_format')->nullable();
          $table->string('number_format')->nullable();
          $table->string('default_language', 5)->nullable();
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
          $table->dropColumn(['date_format', 'number_format', 'default_language']);
      });
  }
}
