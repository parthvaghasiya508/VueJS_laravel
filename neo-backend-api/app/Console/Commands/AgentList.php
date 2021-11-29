<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Exception;
use Illuminate\Console\Command;

class AgentList extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'agent:list
  ';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Lists all agents';

  /**
   * Execute the console command.
   *
   * @return int
   * @throws Exception
   */
  public function handle()
  {
    $list = Agent::query()->withCount(['tokens', 'activeTokens', 'users'])->get()->map(fn (Agent $a) => [
      $a->id, $a->title, $a->name,
      $a->active ? 'YES' : 'NO',
      $a->active_tokens_count.' / '.$a->tokens_count,
      $a->users_count,
      optional($a->group)->name ?? '<none>',
    ]);
    $this->table(['ID', 'Title', 'Name', 'Active', 'Active/Issued Tokens', 'Users', 'Group'], $list);
    return self::SUCCESS;
  }
}
