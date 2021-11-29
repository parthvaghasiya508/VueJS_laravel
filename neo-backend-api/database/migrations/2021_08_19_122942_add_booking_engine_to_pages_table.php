<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

class AddBookingEngineToPagesTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Page::create([
        'name' => 'bookingengine',
        'category' => 'connectivity',
        'for_hotel' => true,
        'display_order' => 85
      ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    optional(Page::firstWhere('name', 'bookingengine'))->delete();
  }
}
