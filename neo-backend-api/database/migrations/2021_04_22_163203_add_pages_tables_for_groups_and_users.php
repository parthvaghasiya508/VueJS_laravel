<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPagesTablesForGroupsAndUsers extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('group_page', function (Blueprint $table) {
      $table->unsignedBigInteger("page_id");
      $table->foreign("page_id")->references("id")->on("pages")->cascadeOnDelete();
      $table->unsignedBigInteger("group_id");
      $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
      $table->primary(['page_id', 'group_id']);
    });
    Schema::create('page_user', function (Blueprint $table) {
      $table->unsignedBigInteger("page_id");
      $table->foreign("page_id")->references("id")->on("pages")->cascadeOnDelete();
      $table->unsignedBigInteger("user_id");
      $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
      $table->primary(['page_id', 'user_id']);
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('group_page');
    Schema::dropIfExists('page_user');
  }
}
