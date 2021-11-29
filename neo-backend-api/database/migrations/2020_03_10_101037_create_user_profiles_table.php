<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfilesTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('user_profiles', function (Blueprint $table) {
      $table->id('user_id');
      $table->string('first_name');
      $table->string('last_name');
      $table->string('hotel_name');
      $table->string('city')->nullable();
      $table->string('zip', 10)->nullable();
      $table->string('tel', 20)->nullable();
      $table->string('street', 255)->nullable();
      $table->timestamps();

      $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('user_profiles');
  }
}
