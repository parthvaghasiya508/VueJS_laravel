<?php

namespace App\Console\Commands;

use App\Managers\PMSManager;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class HotelImport extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'hotel:import
                            {id : Hotel ID}
                            {--U|user= : Hotel owner\'s email}
                            {--G|group= : Group domain}
  ';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Import hotel from CultSwitch and assign it to given user';

  /**
   * Execute the console command.
   *
   * @return int
   * @throws Exception
   */
  public function handle()
  {
    if (!$this->hasOption('user')) {
      $this->error('You should provide an email of hotel owner');
      return 1;
    }
    $email = $this->option('user');
    /** @var User $user */
    $user = User::query()->firstWhere('email', $email);
    if (!$user) {
      $this->error("User with email '$email' not found");
      return 1;
    }
    $id = $this->argument('id');
    $hotel = Hotel::query()->find($id);
    if ($hotel) {
      $this->error("Hotel '$id' already exists");
      return 1;
    }
    /** @var PMSManager $manager */
    $manager = app(PMSManager::class);
    try {
      $hotel = Hotel::create([
        'id'          => $id,
        'ctx'         => 'CLTZ',
        'name'        => '',
        'country'     => 'DE',
        'user_id'     => $user->id,
        'group_id'    => $this->getHotelGroupId($this->option('group')),
      ]);
      $data = $manager->getHotel($hotel);
      $hotel->update($data);
      $this->info("Hotel has been imported successfully");
    } catch (Throwable $e) {
      $this->error($e->getMessage());
      $hotel->delete();
      return 1;
    }
    return 0;
  }

  public function getHotelGroupId(string $domain)
  {
    return optional(Group::firstWhere('b_domain', $domain))->id;
  }
}
