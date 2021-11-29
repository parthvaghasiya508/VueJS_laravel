<?php

use App\Models\Agent;
use App\Models\Hotel;
use Illuminate\Database\Migrations\Migration;

class FixAgentHotelsGroups extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    $agentsGroups = Agent::all()->pluck('group_id', 'id');
    $agentsGroups->each(fn ($group_id, $agent_id) => Hotel::query()->where(compact('agent_id'))->update(compact('group_id')));
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    // do nothing
  }
}
