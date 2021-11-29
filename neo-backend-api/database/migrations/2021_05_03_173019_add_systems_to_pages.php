<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

class AddSystemsToPages extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Page::create([
      'name' => 'systems',
      'category' => 'connectivity',
      'for_hotel' => 1,
      'display_order' => 95,
    ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    optional(Page::firstWhere('name', 'systems'))->delete();
  }
}
