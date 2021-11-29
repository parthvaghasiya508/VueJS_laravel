<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;

class HotelRegistered {

  use SerializesModels;

  public $userId;
  public $data;

  /**
   * Create a new event instance.
   *
   * @param User $user
   * @param array $data
   */
  public function __construct(User $user, $data = [])
  {
    $this->userId = $user->id;
    $this->data = $data;
  }
}
