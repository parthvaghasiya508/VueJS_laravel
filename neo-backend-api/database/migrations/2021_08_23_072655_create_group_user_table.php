<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupUserTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::create('group_user', function (Blueprint $table) {
          $table->unsignedBigInteger("group_id");
          $table->unsignedBigInteger("user_id");
          $table->boolean('all_group_hotels')->default(false);
          $table->foreign("group_id")->references("id")->on("groups")->cascadeOnDelete();
          $table->foreign("user_id")->references("id")->on("users")->cascadeOnDelete();
          $table->primary(["group_id", "user_id"]);
      });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::dropIfExists('group_user');
  }
}
