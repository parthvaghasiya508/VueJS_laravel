<?php

namespace App\Console\Commands;

use App\Managers\PMSManager;
use App\Models\Hotel;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class HotelUpdate extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'hotel:update
                            {id : Hotel ID}
  ';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Update hotel data from CultSwitch';

  /**
   * Execute the console command.
   *
   * @return int
   * @throws Exception
   */
  public function handle()
  {
    $id = $this->argument('id');
    /** @var Hotel $hotel */
    $hotel = Hotel::query()->find($id);
    if (!$hotel) {
      $this->error("Hotel '$id' doesn't exist");
      return 1;
    }
    /** @var PMSManager $manager */
    $manager = app(PMSManager::class);
    try {
      $data = $manager->getHotel($hotel);
      $hotel->update($data);
      $this->info("Hotel has been updated successfully");
    } catch (Throwable $e) {
      $this->error($e->getMessage());
      return 1;
    }
    return 0;
  }
}
