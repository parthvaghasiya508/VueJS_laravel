<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfirmedToRoleUserTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::table('role_user', function (Blueprint $table) {
        $table->boolean('confirmed')->default(true);
      });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::table('role_user', function (Blueprint $table) {
        $table->dropColumn('confirmed');
      });
  }
}
