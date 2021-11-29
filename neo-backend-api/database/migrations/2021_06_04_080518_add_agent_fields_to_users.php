<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgentFieldsToUsers extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('users', function (Blueprint $table) {
      $table->unsignedBigInteger('agent_id')->after('source_id')->nullable()->default(null);
      $table->unsignedBigInteger('agent_user_id')->nullable()->default(null)->after('agent_id')->index();
      $table->foreign('agent_id')->references('id')->on('agents')->nullOnDelete();
      $table->dropColumn('source_id');
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
      $table->unsignedBigInteger('source_id')->after('agent_id')->nullable()->default(null);
      $table->dropColumn(['agent_id', 'agent_user_id']);
    });
  }
}
