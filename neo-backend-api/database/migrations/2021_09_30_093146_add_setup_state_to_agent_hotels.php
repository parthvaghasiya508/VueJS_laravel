<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSetupStateToAgentHotels extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('hotels', function (Blueprint $table) {
      $table->timestamp('agent_setup_at')->nullable()->after('agent_id');
      $table->unsignedTinyInteger('agent_setup_step')->default(0)->after('agent_setup_at')->index();
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
      $table->dropColumn(['agent_setup_at', 'agent_setup_step']);
    });
  }
}
