<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Users\PropertyGroupController;
use App\Http\Requests\PropertyGroupUpdateRequest;
use App\Models\User;
use App\Models\Group;
use App\Repositories\PropertyGroupRepository;
use App\Services\PropertyGroupService;
use Faker\Provider\en_US\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyGroupTest extends TestCase
{
  use WithFaker, DatabaseTransactions;

  /**
   * @group property-group
   * @test
   */
  public function group_was_created_and_image_was_uploaded()
  {
    $user = User::factory()->createOne();

    $this->actingAs($user);

    $service = app(PropertyGroupService::class);

    $data = [
      'name' => $name = $this->faker->firstName,
      'slug' => $this->faker->text('80'),
      'style' => [
        'color_font'=> $this->faker->numberBetween(1, 999),
        'color_schema'=> $this->faker->numberBetween(1, 999),
      ],
    ];

    $logo = File::create('test.jpg');

    $group = $service->create($data, $logo);

    $this->assertInstanceOf(Group::class, $group);
    $this->assertFileExists(Storage::disk('group')->path($group->logo));
  }

  /**
   * @group property-group
   * @test
   */
    public function group_update_and_previous_image_was_deleted(){
      $user = User::factory()->createOne();

      $this->actingAs($user);

      $data = [
        'name' => $name = $this->faker->firstName,
        'slug' => $this->faker->text('30'),
        'style' => json_encode([
          'color_font'=> $this->faker->numberBetween(1, 999),
          'color_schema'=> $this->faker->numberBetween(1, 999),
        ]),
      ];

      $logo = File::create('test.jpg');

      $service = app(PropertyGroupService::class);

      $group = $service->create($data, $logo);
      $old_image_name = $group->getRawOriginal('logo');

      $data = [
        'name' => $name = $this->faker->firstName,
        'slug' => $this->faker->text('30'),
        'style' => json_encode([
          'color_font'=>$this->faker->numberBetween(1, 999),
          'color_schema'=>$this->faker->numberBetween(1, 999),
        ]),
      ];

      $logo = File::create('test.jpg');

      $group = $service->update($data, $logo, $group);

      $this->assertTrue($group);
      $this->assertFileDoesNotExist(Storage::disk('group')->path($old_image_name));
    }

  /**
   * @group property-group
   * @test
   */
  public function group_was_deleted_with_image() {
    $user = User::factory()->createOne();

    $this->actingAs($user);

    $data = [
      'name' => $name = $this->faker->firstName,
      'slug' => $this->faker->text('80'),
      'style' => [
        'color_font'=> $this->faker->numberBetween(1, 999),
        'color_schema'=> $this->faker->numberBetween(1, 999),
      ],
    ];

    $logo = File::create('test.jpg');

    $service = app(PropertyGroupService::class);

    $group = $service->create($data, $logo);
    $old_image_name = $group->logo;
    $deleting = $service->delete($group);
    $this->assertTrue($deleting);
    $this->assertFileDoesNotExist(Storage::disk('group')->path($old_image_name));
  }

  /**
   * @test
   */
  public function q(){
    $user = User::factory()->createOne();
    $a = $this->actingAs($user, 'sanctum')->withHeaders(['Accept' => 'application/json'])->get('api/role/all/58549');
    dd($a->json());
  }
}
