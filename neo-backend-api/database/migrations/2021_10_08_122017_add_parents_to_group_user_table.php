<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentsToGroupUserTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::table('group_user', function (Blueprint $table) {
        $table->json('parents')->nullable();
      });

      User::whereNotNull('parents')->get()->each(function (User $user) {
        $formerParents = $user->makeVisible('parents')->parents->all();
        $groups = [];
        $user->groups->each(function (Group $g) use (&$groups, $formerParents) {
          $not_admin_parents = User::whereIn('id', $formerParents)
            ->whereHas('groups', fn ($q) => $q->where('id', $g->id))
            ->get();
          $admin_parent = User::whereIn('id', $formerParents)->where('admin', true)->get();
          $parents = $admin_parent->merge($not_admin_parents)->unique('id')->values();

          $groups[$g->id] = [
            "user_id" => $g->pivot->user_id,
            "group_id" => $g->pivot->group_id,
            "all_group_hotels" => $g->pivot->all_group_hotels,
            "parents" => $parents->pluck('id')
          ];
        });

        $user->groups()->sync($groups);
      });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::table('group_user', function (Blueprint $table) {
          $table->dropColumn('parents');
      });
  }
}
