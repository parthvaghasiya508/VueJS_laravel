<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('pages', function (Blueprint $table) {
      $table->id();
      $table->string('name')->unique();
    });
    # create page
    $pages_key = [
      'dashboard',
      'reservations',
      'calendar',
      'roomtypes',
      'rateplans',
      'mealplans',
      'photos',
      'policies',
      'channels',
      'masterdata',
      'booking',
      'description',
      'contactpersons',
      'nearby',
      'facilities',
      'groups',
      'users',
    ];
    foreach ($pages_key as $name) {
      Page::create(compact('name'));
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('pages');
  }
}
