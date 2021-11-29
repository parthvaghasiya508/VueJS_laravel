<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

class UpdatePagesTableToSetForHotelAttribute extends Migration
{
  protected array $pages = ['group', 'hotels', 'users'];

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    $this->setValueForForHotelAttribute(false);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    $this->setValueForForHotelAttribute(true);
  }

  public function setValueForForHotelAttribute(bool $value)
  {
    Page::whereIn('name', $this->pages)->update([
      'for_hotel' => $value
    ]);
  }
}
