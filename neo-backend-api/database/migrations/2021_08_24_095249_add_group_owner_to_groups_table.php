<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupOwnerToGroupsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::table('groups', function (Blueprint $table) {
          $table->unsignedBigInteger('group_owner')->nullable();
          $table->foreign("group_owner")->references("id")->on("users");
      });

      if ($this->seedGroupOwner()) {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("group_owner");
        });
      };
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('users', function (Blueprint $table) {
      $table->boolean('group_owner')->default(false);
    });
    if ($this->revertSeedingGroupOwner()) {
      Schema::table('groups', function (Blueprint $table) {
        $table->dropConstrainedForeignId("group_owner");
      });
    }
  }

  public function seedGroupOwner()
  {
    User::where('group_owner', 1)->get()->each(function (User $user) {
      Group::whereId($user->group_id)->update(["group_owner" => $user->id]);
    });
    return true;
  }

  public function revertSeedingGroupOwner()
  {
    Group::whereNotNull('group_owner')->get()->each(function (Group $group) {
      User::whereId($group->group_owner)->update(["group_owner" => true]);
    });
    return true;
  }
}
