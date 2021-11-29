<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesPermissionsTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('page_role', function (Blueprint $table) {
      $table->unsignedBigInteger("page_id");
      $table->foreign("page_id")->references("id")->on("pages")->cascadeOnDelete();
      $table->unsignedBigInteger("role_id");
      $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
      $table->primary(['page_id', 'role_id']);
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('page_role');
  }
}
