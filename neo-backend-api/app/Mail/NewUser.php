<?php

namespace App\Mail;

use App\Events\UserRegistered;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;

class NewUser extends Mailable {

  private $event;
  private $sendTo;

  /**
   * Create a new message instance.
   *
   * @param UserRegistered $event
   * @param string $to
   */
  public function __construct(UserRegistered $event, string $to)
  {
    parent::__construct();
    $this->event = $event;
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
    $user = $this->event->user;
    $group = $this->event->group;
    $url = (new VerifyEmailNotification($group))->getUrl($user);
    return $this->to($this->sendTo)
                ->subject("[report:{$user->id}] New registration")
                ->view('emails.info.registered', [
                  'id'    => $user->id,
                  'email' => $user->email,
                  'url'   => $url,
                  'date'  => $user->created_at->format('Y-m-d H:i:s'),
                ]);
  }
}
