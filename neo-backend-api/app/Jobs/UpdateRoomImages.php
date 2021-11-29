<?php

namespace App\Jobs;

use App\Managers\PMSManager;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateRoomImages implements ShouldQueue {

  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  private int $hotel_id;
  private string $room;

  /**
   * Create a new job instance.
   *
   * @param string $room
   * @param int $hotel_id
   */
  public function __construct(string $room, int $hotel_id)
  {
    $this->onConnection('database')->onQueue('room_images');
    $this->room = $room;
    $this->hotel_id = $hotel_id;
  }

  /**
   * Execute the job.
   *
   * @param PMSManager $manager
   *
   * @return void
   */
  public function handle(PMSManager $manager)
  {
    /** @var Hotel $hotel */
    if(!$hotel = Hotel::query()->find($this->hotel_id)) {
      return;
    }
    try {
      $manager->setCredentials($hotel);
      $payload = $manager->getRoomType(['pid' => $this->room]);
      $manager->modifyRoomType($payload);
    } catch(Throwable $e) {
      Log::channel('pms')->error($e->getMessage());
    }
  }
}
