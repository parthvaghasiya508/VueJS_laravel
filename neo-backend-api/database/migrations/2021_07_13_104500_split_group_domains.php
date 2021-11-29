<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SplitGroupDomains extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('groups', function (Blueprint $table) {
      $table->renameColumn('domain_locked', 'domains_locked');
      $table->renameColumn('domain_status', 'domains_status');
      $table->string('b_domain', 255)->after('domain')->nullable();
      $table->renameColumn('domain', 'e_domain');
    });
    DB::table('groups')->update([
      'b_domain' => DB::raw('REPLACE(`e_domain`, "admin.", "")'),
    ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('groups', function (Blueprint $table) {
      $table->renameColumn('domains_locked', 'domain_locked');
      $table->renameColumn('domains_status', 'domain_status');
      $table->renameColumn('e_domain', 'domain');
      $table->dropColumn('b_domain');
    });
  }
}
