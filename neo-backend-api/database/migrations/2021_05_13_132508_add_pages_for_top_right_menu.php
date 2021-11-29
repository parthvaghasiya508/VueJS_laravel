<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

class AddPagesForTopRightMenu extends Migration {

  private array $pages = [
    ['name' => 'invoices', 'category' => 'other', 'for_hotel' => true, 'display_order' => 200],
    ['name' => 'profile', 'category' => 'other', 'for_hotel' => false, 'display_order' => 210],
    ['name' => 'legal', 'category' => 'other', 'for_hotel' => true, 'display_order' => 220],
  ];

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    foreach ($this->pages as $page) {
      Page::create($page);
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    $names = array_column($this->pages, 'name');
    Page::query()->whereIn('name', $names)->delete();
  }
}
