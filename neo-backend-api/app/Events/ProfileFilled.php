<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class ProfileFilled {

  use SerializesModels;

  public $userId;

  /**
   * Create a new event instance.
   *
   * @param User $user
   */
  public function __construct(User $user)
  {
    $this->userId = $user->id;
  }
}
