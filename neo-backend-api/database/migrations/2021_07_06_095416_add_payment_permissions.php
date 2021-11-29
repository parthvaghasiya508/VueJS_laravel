<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Page;


class AddPaymentPermissions extends Migration
{
  private array $pages = [
    ['name' => 'payments', 'for_hotel' => true, 'category' => 'billing', 'display_order' => 300],
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
