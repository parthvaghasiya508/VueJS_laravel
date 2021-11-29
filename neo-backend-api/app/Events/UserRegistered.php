<?php

namespace App\Events;

use App\Models\Group;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class UserRegistered {

  use SerializesModels;

  public $user;
  public $group;

  /**
   * Create a new event instance.
   *
   * @param User $user
   */
  public function __construct(User $user)
  {
    $this->user = $user;
    $this->group = Group::getCurrent();
  }
}
