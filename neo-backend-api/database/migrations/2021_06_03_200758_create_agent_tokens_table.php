<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentTokensTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('agent_tokens', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('agent_id');
      $table->char('token', 32)->index();
      $table->boolean('revoked')->default(false);
      $table->timestamp('revoked_at')->nullable();
      $table->foreign('agent_id')->references('id')->on('agents')->cascadeOnDelete();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('agent_tokens');
  }
}
