<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

class PagesFixManagementCategory extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Page::query()->where('category', 'administration')->update(['category' => 'management']);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Page::query()->where('category', 'management')->update(['category' => 'administration']);
  }
}
