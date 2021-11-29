<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContractsToPages extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Page::create([
      'name' => 'contracts',
      'category' => 'products',
      'for_hotel' => true,
      'display_order' => 67,
    ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    optional(Page::firstWhere('name', 'contracts'))->delete();
  }
}
