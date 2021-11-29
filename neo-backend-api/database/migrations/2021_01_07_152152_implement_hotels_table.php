<?php

use App\Models\Hotel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImplementHotelsTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    // hotels table
    Schema::create('hotels', function (Blueprint $table) {
      $table->unsignedBigInteger('id')->primary();
      $table->string('ctx', 20);
      $table->string('name', 250);
      $table->unsignedBigInteger('user_id')->index();
      $table->unsignedBigInteger('currency_id')->index();
      $table->boolean('active')->default(true);
      $table->timestamps();
    });
    // copy data from user_profiles
    DB::table('user_profiles')->whereNotNull('hotel_id')->get()->each(function ($row) {
      $hotel = [
        'id'          => $row->hotel_id,
        'ctx'         => $row->hotel_ctx,
        'name'        => $row->hotel_name,
        'user_id'     => $row->user_id,
        'currency_id' => $row->currency_id,
        'active'      => true,
      ];
      Hotel::create($hotel);
    });
    Schema::table('user_profiles', function (Blueprint $table) {
      $table->dropColumn([
        'primary_email', 'alternative_email', 'hotel_name', 'hotel_id', 'hotel_ctx',
        'federal_state', 'capacity_mode', 'capacity', 'currency_id', 'website',
        'city', 'zip', 'street', 'street_optional', 'latitude', 'longitude',
      ]);
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('hotels');
  }
}
