<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class SetupComplete {

  use SerializesModels;

  public $userId;
  public $data;

  /**
   * Create a new event instance.
   *
   * @param User $user
   * @param array|null $data
   */
  public function __construct(User $user, $data = null)
  {
    $this->userId = $user->id;
    $this->data = $data;
  }

}
