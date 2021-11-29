<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormattedTelephoneToHotelsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::table('hotels', function (Blueprint $table) {
          $table->string('tel_ntl')->nullable();
          $table->string('tel_intl')->nullable();
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
          $table->dropColumn('tel_ntl');
          $table->dropColumn('tel_intl');
      });
  }
}
