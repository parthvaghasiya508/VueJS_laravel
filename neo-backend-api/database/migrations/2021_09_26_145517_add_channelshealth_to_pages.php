<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChannelshealthToPages extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Page::create([
      'name' => 'channelshealth',
      'category' => 'systemhealth',
      'for_hotel' => 0,
      'display_order' => 190,
    ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    optional(Page::firstWhere('name', 'channelshealth'))->delete();
  }
}
