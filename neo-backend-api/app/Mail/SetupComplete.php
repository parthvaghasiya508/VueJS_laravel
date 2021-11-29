<?php

namespace App\Mail;

use App\Models\User;

class SetupComplete extends Mailable {

  private $userId;
  private $sentTo;
  private $data;

  /**
   * Create a new message instance.
   *
   * @param int $userId
   * @param string $to
   * @param array|null $data
   */
  public function __construct($userId, $to, $data = null)
  {
    $this->userId = $userId;
    $this->sentTo = $to;
    $this->data = $data;
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
    $state = !!$user->setup_step ? 'complete' : 'cancelled';
    $data = [
        'user'  => $user,
        'state' => $state,
      ] + ($this->data ?? []);
    return $this->to($this->sentTo)
                ->subject("[report:{$user->id}] Setup {$state}")
                ->view('emails.info.setup', $data);
  }
}
