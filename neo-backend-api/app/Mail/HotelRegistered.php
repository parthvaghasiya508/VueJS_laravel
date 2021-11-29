<?php

namespace App\Mail;

use App\Managers\PMSManager;
use App\Models\Hotel;
use App\Models\User;

class HotelRegistered extends Mailable {

  private $userId;
  private $sendTo;
  private $data;

  /**
   * Create a new message instance.
   *
   * @param int $userId
   * @param string $to
   * @param array $data
   */
  public function __construct($userId, $to, $data = [])
  {
    parent::__construct();
    $this->userId = $userId;
    $this->sendTo = $to;
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
    $data = [
      'user'         => $user,
      'created_at'   => $user->created_at,
      'pd_filled_at' => $user->pd_filled_at,
      'cd_filled_at' => $user->cd_filled_at,
    ];
    if (!$data['skip'] = isset($this->data['skip'])) {
      $data['hotel'] = Hotel::query()->find($this->data['hotel']);
      $data['country'] = $this->getCountry($this->data['country']);
      $data += $this->data;
    }
    return $this->to($this->sendTo)
                ->subject("[report:{$user->id}] Hotel registration")
                ->view('emails.info.hotelreg', $data);
  }

  /**
   * Get Country
   * with a given code (ISO)
   *
   * @param string $code
   * @return array|null
   */
  public function getCountry(string $code)
  {
    $manager = app()->make(PMSManager::class);
    return $manager->getCountry($code);
  }
}
