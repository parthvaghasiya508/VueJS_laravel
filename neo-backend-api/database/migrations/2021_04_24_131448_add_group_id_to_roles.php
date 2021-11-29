<?php

use App\Models\Hotel;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupIdToRoles extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('roles', function (Blueprint $table) {
      $table->unsignedBigInteger('group_id')->after('user_id')->index()->nullable();
      $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
    });
    $ids = Role::query()->distinct()->get(['hotel_id'])->pluck('hotel_id');
    $hotels = Hotel::query()->select(['id', 'group_id'])->findMany($ids)->pluck('group_id', 'id');
    foreach ($hotels as $hotel_id => $group_id) {
      $q = Role::query()->where(compact('hotel_id'));
      if ($group_id) {
        $q->update(compact('group_id'));
      } else {
        $q->delete();
      }
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('roles', function (Blueprint $table) {
      $table->dropConstrainedForeignId('group_id');
    });
  }
}
