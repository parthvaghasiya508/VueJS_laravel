<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Group;
use Exception;
use Illuminate\Console\Command;

class AgentUpdate extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'agent:update
                            {name : Agent name}
                            {--G|group= : Linked group ID}
                            {--A|activate : Activate agent}
                            {--D|deactivate : Deactivate agent}
  ';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Update agent data or activate/deactivate it';

  /**
   * Execute the console command.
   *
   * @return int
   * @throws Exception
   */
  public function handle(): int
  {
    $name = $this->argument('name');
    $agent = Agent::findByName($name);
    if (!$agent) {
      $this->error("Agent [$name] not found");
      return self::FAILURE;
    }
    $a = $this->option('activate');
    $d = $this->option('deactivate');
    $group = $this->option('group');
    if ($a && $d) {
      $this->error("Both 'activate' and 'deactivate' options can't be set together");
      return self::FAILURE;
    }
    if ($group !== $agent->group_id) {
      if ($group && !($g = Group::find($group))) {
        $this->error("Group doesn't exist");
        return self::FAILURE;
      }
      $this->comment("Changing agent group to ".($group ? "'{$g->name}'" : 'NULL'));
      $agent->group_id = $group ?: null;
    }
    if ($a && !$agent->active) {
      $this->comment("Activating agent");
      $agent->active = true;
    }
    if ($d && $agent->active) {
      $this->comment("Deactivating agent");
      $agent->active = false;
    }
    if ($agent->isDirty()) {
      $agent->save();
    } else {
      $this->line("Nothing to do");
    }
    return self::SUCCESS;
  }
}
