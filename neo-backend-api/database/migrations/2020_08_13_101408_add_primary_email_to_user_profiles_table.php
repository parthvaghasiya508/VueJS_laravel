<?php

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryEmailToUserProfilesTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('user_profiles', function (Blueprint $table) {
      $table->string('primary_email')->after('last_name');
    });
    // Fill in newly added field with values from users table
    User::query()->get(['id','email'])->each(function (User $u) {
      $p = UserProfile::query()->find($u->id);
      if ($p) {
        $p->update(['primary_email' => $u->email]);
      }
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('user_profiles', function (Blueprint $table) {
      $table->dropColumn(['primary_email']);
    });
  }
}
