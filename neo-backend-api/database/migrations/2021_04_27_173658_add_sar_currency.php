<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;

class AddSarCurrency extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    if (!Currency::findByCode('SAR')) {
      Currency::create([
        'name'   => 'Saudi riyal',
        'code'   => 'SAR',
        'symbol' => '﷼',
      ]);
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    optional(Currency::findByCode('SAR'))->delete();
  }
}
