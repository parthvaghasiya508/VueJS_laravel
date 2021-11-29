<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDomainSettingsToGroups extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('groups', function (Blueprint $table) {
      $table->renameColumn('slug', 'domain');
      $table->boolean('domain_locked')->default(false)->index()->after('name');
      $table->unsignedTinyInteger('domain_status')->default(0)->index()->after('name');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('groups', function (Blueprint $table) {
      $table->renameColumn('domain', 'slug');
      $table->dropColumn(['domain_locked', 'domain_status']);
    });
  }
}
