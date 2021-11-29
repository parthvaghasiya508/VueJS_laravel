<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOneTimeLoginLinksTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('one_time_login_links', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->char('uuid', 36)->index();
      $table->timestamp('expire_at');
      $table->timestamp('used_at')->nullable();
      $table->string('exit_url', 500)->nullable();
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
    Schema::dropIfExists('one_time_login_links');
  }
}
