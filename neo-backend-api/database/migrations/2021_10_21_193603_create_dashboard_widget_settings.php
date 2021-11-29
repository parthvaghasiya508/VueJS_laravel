<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDashboardWidgetSettings extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::create('dashboard_widget_settings', function (Blueprint $table) {
          $table->id();
          $table->unsignedBigInteger('user_id')->index();
          $table->unsignedInteger('widget_group');
          $table->unsignedInteger('widget_id');
          $table->unsignedTinyInteger('visible');
          $table->unsignedInteger('x');
          $table->unsignedInteger('y');
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
      Schema::dropIfExists('dashboard_widget_settings');
  }
}
