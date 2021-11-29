<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveGroupIdAndAllHotelsGroupFromUsersTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      $this->seedGroupUserPivotTable();
      Schema::table('users', function (Blueprint $table) {
          $table->dropConstrainedForeignId("group_id");
          $table->dropColumn("all_group_hotels");
      });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::table('users', function (Blueprint $table) {
          $table->unsignedBigInteger("group_id");
          $table->boolean('all_group_hotels')->default(false);
          $table->foreign("group_id")->references("id")->on("groups")->cascadeOnDelete();
      });
  }

  public function seedGroupUserPivotTable()
  {
    User::all()->each(function (User $user) {
      $attributes = [
        "all_group_hotels" => $user->all_group_hotels,
      ];
      $user->groups()->attach($user->group_id, $attributes);
    });
  }
}
