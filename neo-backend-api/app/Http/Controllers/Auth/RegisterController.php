<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Page;
use App\Models\User;
use App\Support\Domain;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller {

  /*
  |--------------------------------------------------------------------------
  | Register Controller
  |--------------------------------------------------------------------------
  |
  | This controller handles the registration of new users as well as their
  | validation and creation. By default this controller uses a trait to
  | provide this functionality without requiring any additional code.
  |
  */

  use RegistersUsers;

  /**
   * Where to redirect users after registration.
   *
   * @return string
   */
  protected function redirectTo()
  {
    return Domain::getEffectiveExtranetDomain();
  }

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('guest');
    $this->middleware('throttle:6,1')->only('register');
  }

  /**
   * Get a validator for an incoming registration request.
   *
   * @param array $data
   *
   * @return \Illuminate\Contracts\Validation\Validator
   */
  protected function validator(array $data)
  {
    return Validator::make($data, [
      'email'      => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'password'   => ['required', 'string', 'min:8'],
      'tos_agreed' => ['accepted'],
    ], [
      'tos_agreed.accepted' => 'You must accept this',
    ]);
  }

  /**
   * Create a new user instance after a valid registration.
   *
   * @param array $data
   *
   * @return User
   */
  protected function create(array $data)
  {
    $group = Group::getCurrent();
    $root = User::firstWhere('email', config('root.root_email'));
    if ($group && !$group->owner) abort(404, 'Group has not got an owner !');

    /** @var User $user */
    $payload = [
      'parent'     => $group ? $group->owner->id : $root->id,
      'email'      => $data['email'],
      'password'   => Hash::make($data['password']),
      'tos_agreed' => $data['tos_agreed'],
    ];
    $user = User::unguarded(fn () => User::create($payload));

    if ($group) {
      $user->groups()->attach($group->id, ['parents' =>  [$group->owner->id]]);
      // give user all available permissions, except "Group"
      $pages = $group->pages->reject(fn (Page $p) => in_array($p->name, Page::GROUP_PROTECTED_PAGES))->pluck('name');
      $user->pages()->attach(Page::idsByNames($pages));
    }
    return $user->fresh();
  }

  protected function registered(Request $request, $user)
  {
    event(new UserRegistered($user));
  }
}
