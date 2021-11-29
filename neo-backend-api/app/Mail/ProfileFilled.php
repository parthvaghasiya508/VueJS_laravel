<?php

namespace App\Mail;

use App\Models\User;

class ProfileFilled extends Mailable {

  private $userId;
  private $sendTo;

  /**
   * Create a new message instance.
   *
   * @param int $userId
   * @param string $to
   */
  public function __construct($userId, $to)
  {
    parent::__construct();
    $this->userId = $userId;
    $this->sendTo = $to;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {
    /** @var User $user */
    $user = User::query()->find($this->userId);
    $data = [
      'user'         => $user,
      'created_at'   => $user->created_at,
      'pd_filled_at' => $user->pd_filled_at,
    ];
    return $this->to($this->sendTo)
                ->subject("[report:{$user->id}] Profile filled")
                ->view('emails.info.profile', $data);
  }
}
