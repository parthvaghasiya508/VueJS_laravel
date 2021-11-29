<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentsToUsers extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('users', function (Blueprint $table) {
      $table->unsignedBigInteger('parent_id')->nullable()->after('source_id');
      $table->foreign('parent_id')->references('id')->on('users');
      $table->char('lang', 2)->default('en')->after('parent_id');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropConstrainedForeignId('parent_id');
      $table->dropColumn(['lang']);
    });
  }
}
