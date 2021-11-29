<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AddRootUserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $exist = User::where('email', config('root.root_email'))->exists();
    if ($exist) return null;

    /** @var User $user */
    # Create user
    $user = User::create([
      'email'      => config('root.root_email'),
      'password'   => Hash::make(config('root.root_password')),
      'tos_agreed' => true,
      'admin'      => true,
    ]);
    $user->email_verified_at = Carbon::now();
    $user->save();

    $data = [
      'first_name' => 'Root',
      'last_name'  => 'Extranet',
    ];
    $user->updatePersonalDetails($data);
    $user->cd_filled_at = $user->freshTimestamp();
    $user->setup_at = $user->freshTimestamp();
    $user->save();
  }
}
