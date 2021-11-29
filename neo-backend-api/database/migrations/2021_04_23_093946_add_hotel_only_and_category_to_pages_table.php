<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class AddHotelOnlyAndCategoryToPagesTable extends Migration {

  private array $pages = [
    ['name' => 'dashboard', 'category' => null, 'for_hotel' => true, 'display_order' => 10],
    ['name' => 'reservations', 'category' => null, 'for_hotel' => true, 'display_order' => 20],
    ['name' => 'calendar', 'category' => null, 'for_hotel' => true, 'display_order' => 30],
    ['name' => 'roomtypes', 'category' => 'products', 'for_hotel' => true, 'display_order' => 40],
    ['name' => 'rateplans', 'category' => 'products', 'for_hotel' => true, 'display_order' => 50],
    ['name' => 'mealplans', 'category' => 'products', 'for_hotel' => true, 'display_order' => 60],
    ['name' => 'photos', 'category' => 'products', 'for_hotel' => true, 'display_order' => 70],
    ['name' => 'policies', 'category' => 'products', 'for_hotel' => true, 'display_order' => 80],
    ['name' => 'channels', 'category' => 'connectivity', 'for_hotel' => true, 'display_order' => 90],
    ['name' => 'masterdata', 'category' => 'property', 'for_hotel' => true, 'display_order' => 100],
    ['name' => 'booking', 'category' => 'property', 'for_hotel' => true, 'display_order' => 110],
    ['name' => 'description', 'category' => 'property', 'for_hotel' => true, 'display_order' => 120],
    ['name' => 'contactpersons', 'category' => 'property', 'for_hotel' => true, 'display_order' => 130],
    ['name' => 'nearby', 'category' => 'property', 'for_hotel' => true, 'display_order' => 140],
    ['name' => 'facilities', 'category' => 'property', 'for_hotel' => true, 'display_order' => 150],
    ['name' => 'group', 'category' => 'administration', 'for_hotel' => false, 'display_order' => 160],
    ['name' => 'hotels', 'category' => 'administration', 'for_hotel' => false, 'display_order' => 170],
    ['name' => 'users', 'category' => 'administration', 'for_hotel' => false, 'display_order' => 180],
  ];

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('pages', function (Blueprint $table) {
      $table->string('category')->nullable();
      $table->boolean('for_hotel')->index()->default(true);
      $table->unsignedMediumInteger('display_order')->default(0);
    });
    Page::all()->each->delete();
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
    Schema::table('pages', function (Blueprint $table) {
      $table->dropColumn(['for_hotel', 'category', 'display_order']);
    });
    Page::all()->each->delete();
    foreach ($this->pages as $page) {
      Page::create(Arr::only($page, 'name'));
    }
  }
}
