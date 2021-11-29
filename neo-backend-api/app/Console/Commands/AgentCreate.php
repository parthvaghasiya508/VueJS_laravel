<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AgentCreate extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'agent:create
                            {title : Agent Title}
                            {--T|token : Also create agent token}
                            {--A|activate : Activate newly created agent}
  ';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Creates agent with or without its first API token';

  /**
   * Execute the console command.
   *
   * @return int
   * @throws Exception
   */
  public function handle(): int
  {
    $title = trim($this->argument('title'));
    $name = Str::slug($title);
    $a = $this->option('activate');
    $t = $this->option('token');
    $_a = $a ? ' and activating' : '';
    $_t = $t ? ' with new token' : '';
    $this->comment("Creating{$_a} agent \"$title\" [$name]{$_t}");
    if (Agent::findByName($name)) {
      $this->error("Agent with name [$name] already exists");
      return self::FAILURE;
    }
    $agent = Agent::createNew($title, $name, $a);
    $this->info("AGENT: ID={$agent->id} NAME={$agent->name} ACTIVE=".($a ? 'YES' : 'NO'));
    if ($t) {
      $token = $agent->issueToken();
      $this->info("TOKEN: ID={$token->id} TOKEN={$token->token}");
    }
    return self::SUCCESS;
  }
}
