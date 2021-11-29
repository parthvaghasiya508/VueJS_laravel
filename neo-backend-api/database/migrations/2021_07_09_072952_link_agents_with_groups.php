<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkAgentsWithGroups extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('agents', function (Blueprint $table) {
      $table->dropColumn('engine_url');
      $table->unsignedBigInteger('group_id')->after('name')->nullable();
      $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('agents', function (Blueprint $table) {
      $table->string('engine_url', 500)->after('name')->nullable();
      $table->dropConstrainedForeignId('group_id');
    });
  }
}
