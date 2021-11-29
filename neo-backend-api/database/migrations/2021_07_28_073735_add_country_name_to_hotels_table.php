<?php

use App\Managers\PMSManager;
use App\Models\Hotel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class AddCountryNameToHotelsTable extends Migration
{
  public function __construct()
  {
      $this->manager = app(PMSManager::class);
  }

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::table('hotels', function (Blueprint $table) {
        $table->string('country_name');
      });

      $this->updateCountryNameValueForExistingHotels();
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::table('hotels', function (Blueprint $table) {
          $table->dropColumn('country_name');
      });
  }

  public function updateCountryNameValueForExistingHotels()
  {
    $countries = $this->manager->getCountriesOrStates();
    $updates = [];

    Hotel::get()->each(function (Hotel $hotel) use ($countries, &$updates) {
      $country = Arr::first($countries, fn($value) => $value['code'] == optional($hotel)->country);

        if ($country) {
          $updates[] = [
            'id' => $hotel->id,
            'name' => $hotel->name,
            'ctx' => $hotel->ctx,
            'country' => $hotel->country,
            'country_name' => $country['name'],
            'user_id' => $hotel->user_id,
            'currency_id' => $hotel->currency_id,
            'active' => $hotel->active,
          ];
        }
    });

    if (count($updates) == 0) return false;
    return Hotel::upsert($updates, ['id'], ['country_name']);
  }
}
