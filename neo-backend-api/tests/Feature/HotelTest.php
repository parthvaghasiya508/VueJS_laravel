<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Group;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class HotelTest extends TestCase
{
  use  WithFaker;

  /**
   * @group test
   * @test
   */
  public function testExample()
  {
    $id = Group::create([
      'name' => 1,
      'slug' => $this->faker->slug,
      'user_id' => 1,
      'logo' => '',
      'style' => '{}'
    ])->id;
    $ids = $this->faker->randomNumber(4);
    $data = [
      'id' => $ids,
      'ctx' => $this->faker->word(1),
      'name' => $this->faker->title,
      'user_id' => 1,
      'currency_id' => 1,
      'country' => 'DE',
      'group_id' => $id,
      'active' => 0
    ];

    $model = Hotel::create($data);
    dd($model);
  }
}
