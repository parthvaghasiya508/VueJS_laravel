<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PreventSetupForExistingUsers extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    DB::table('users')->whereNotNull('cd_filled_at')->update(['setup_at' => DB::raw('NOW()'), 'setup_step' => 0]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    DB::table('users')->whereNotNull('cd_filled_at')->update(['setup_at' => null, 'setup_step' => 0]);
  }
}
