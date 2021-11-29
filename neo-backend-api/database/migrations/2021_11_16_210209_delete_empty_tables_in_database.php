<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DeleteEmptyTablesInDatabase extends Migration
{
  protected $tables = [
    'customizations',
    'oauth_access_tokens',
    'oauth_auth_codes',
    'oauth_clients',
    'oauth_personal_access_clients',
    'oauth_refresh_tokens',
    'personal_access_tokens',
    'property_groups',
    'subscriptions',
    'subscription_items'
  ];
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    foreach ($this->tables as $table) {
      Schema::disableForeignKeyConstraints();
      Schema::dropIfExists($table);
      Schema::enableForeignKeyConstraints();
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      // No reverse actions needed
  }
}
