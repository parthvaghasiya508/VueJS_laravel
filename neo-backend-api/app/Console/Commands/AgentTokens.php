<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\AgentToken;
use Exception;
use Illuminate\Console\Command;

class AgentTokens extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'agent:tokens
                            {name : Agent name}
                            {--R|revoke= : Revokes token by its ID}
                            {--I|issue : Issue a new token}
  ';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Shows, issues or revokes agent tokens';

  /**
   * Execute the console command.
   *
   * @return int
   * @throws Exception
   */
  public function handle()
  {
    $name = $this->argument('name');
    $agent = Agent::findByName($name);
    if (!$agent) {
      $this->error("Agent [$name] not found");
      return self::FAILURE;
    }
    if ($id = $this->option('revoke')) {
      /** @var AgentToken $token */
      $token = $agent->tokens->firstWhere('id', $id);
      if (!$token) {
        $this->error("Token with ID=$id not found");
        return self::FAILURE;
      }
      if ($token->revoked) {
        $this->warn("Token with ID=$id has already been revoked");
      } else {
        $this->info("Revoking token with ID=$id");
        $token->revoke();
      }
    }
    if ($this->option('issue')) {
      $this->info("Issuing new token");
      $agent->issueToken();
    }
    $list = $agent->tokens->map(fn (AgentToken $at) => [
      $at->id,
      $at->token,
      $at->created_at->format('Y-m-d H:i:s'),
      $at->revoked ? 'YES' : 'NO',
      optional($at->revoked_at)->format('Y-m-d H:i:s'),
      $at->revoked ? '' : base64_encode("{$agent->name}:{$at->token}"),
    ]);
    $this->table(['ID', 'Token', 'Created At', 'Revoked', 'Revoked At', 'Bearer'], $list);
    return self::SUCCESS;
  }
}
