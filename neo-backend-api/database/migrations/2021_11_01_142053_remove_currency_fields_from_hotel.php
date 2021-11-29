<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveCurrencyFieldsFromHotel extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('hotels', function($table) {
      $table->dropColumn('currency_id');
      $table->dropColumn('currency_code');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('hotels', function($table) {
      $table->integer('currency_id');
      $table->string('currency_code');
    });
  }
}
