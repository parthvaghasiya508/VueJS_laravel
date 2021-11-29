<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class AddParentToUsersTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('parent')->nullable()->references('id')->on('users')->nullOnDelete();
    });
    $this->runRootUserSeeder();
    $this->fillParentField();
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::table('users', function (Blueprint $table) {
          $table->dropConstrainedForeignId('parent');
      });
  }

  public function runRootUserSeeder()
  {
    Artisan::call('db:seed', ['--class' => 'AddRootUserSeeder',]);
  }

  /**
   * Fil parent field
   * in users table
   */
  public function fillParentField()
  {
    $root = User::firstWhere('email', config('root.root_email'));

    // Users who has got group
    User::whereHas('groups')->get()->map(function (User $user) use ($root) {
      $g = $user->groups->first();

      if ($user->ownsGroup($g)) $user->parent = $root->id;
      else {
        $user->parent = $g->group_owner;
      }
      $user->save();
    });

    // User who has not got group
    User::whereDoesntHave('groups')->where('email', '!=' , config('root.root_email'))
      ->update(['parent' => $root->id]);
  }
}
