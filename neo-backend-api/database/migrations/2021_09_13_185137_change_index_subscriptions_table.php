<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeIndexSubscriptionsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('subscriptions', function (Blueprint $table)
    {
      $table->dropColumn('user_id');
      $table->unsignedBigInteger('hotel_id');

      $table->index(['hotel_id', 'stripe_status']);
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('subscriptions', function (Blueprint $table) {
      $table->unsignedBigInteger('user_id');
      $table->dropColumn('hotel_id');

      $table->index(['user_id', 'stripe_status']);
    });
  }
}
