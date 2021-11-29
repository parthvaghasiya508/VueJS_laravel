<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentHotelResource;
use App\Http\Resources\AgentHotelsCollection;
use App\Http\Resources\AgentOTLResource;
use App\Http\Resources\AgentUserResource;
use App\Http\Resources\AgentUsersCollection;
use App\Models\Agent;
use App\Models\Hotel;
use App\Models\User;
use App\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller {

  /**
   * @param Request $request
   * @param Agent $agent
   * @param User|null $user
   *
   * @return array
   * @throws ValidationException
   */
  private function validatePayload(Request $request, Agent $agent, User $user = null)
  {
    $requiredUnlessEdit = $user ? 'sometimes' : 'required';
    $rules = [
      'email'              => [$requiredUnlessEdit, 'string', 'email', 'max:255'], // should be in array form
      'password'           => [$requiredUnlessEdit, 'string', new Password()],
      'profile.first_name' => [$requiredUnlessEdit, 'string', 'max:255'],
      'profile.last_name'  => [$requiredUnlessEdit, 'string', 'max:255'],
      'profile.tel'        => [$requiredUnlessEdit, 'regex:/^\+\d{8,15}$/'],
//      'lang'               => [$requiredUnlessEdit, 'string', 'size:2'],
      'avatar.upload'      => 'nullable|file|mimetypes:image/png,image/jpeg',
    ];
    $uniq = Rule::unique('users');
    if ($user) {
      $uniq = $uniq->ignore($user->id);
      $rules += [
        'avatar.remove' => 'boolean',
      ];
    } else {
      $rules += [
        'id' => 'required|numeric',
      ];
    }
    $rules['email'][] = $uniq;
    $payload = $request->validate($rules);
    if (!$user && $agent->users()->where('agent_user_id', Arr::get($payload, 'id'))->exists()) {
      throw ValidationException::withMessages([
        'id' => ['User already exists'],
      ]);
    }
    return $payload;
  }

  /**
   * @param User $agentUser
   *
   * @return AgentUserResource
   */
  public function get(User $agentUser): AgentUserResource
  {
    return AgentUserResource::make($agentUser);
  }

  /**
   * @return AgentUsersCollection
   */
  public function list(): AgentUsersCollection
  {
    $agent = $this->agent();
    return AgentUserResource::collection($agent->users);
  }

  /**
   * @param User $agentUser
   *
   * @return AgentHotelsCollection
   */
  public function listOfHotels(User $agentUser): AgentHotelsCollection
  {
    return AgentHotelResource::collection($agentUser->agentHotels);
  }

  /**
   * Create a new agent user
   *
   * @param Request $request
   *
   * @return AgentUserResource
   */
  public function create(Request $request): AgentUserResource
  {
    $agent = $this->agent();
    $payload = $this->validatePayload($request, $agent);
    $agentUser = User::createNewFromAgent($agent, $payload);
    return AgentUserResource::make($agentUser);
  }

  /**
   * Update an agent user
   *
   * @param Request $request
   * @param User $agentUser
   *
   * @return AgentUserResource
   * @throws ValidationException
   */
  public function update(Request $request, User $agentUser): AgentUserResource
  {
    $agent = $this->agent();
    $payload = $this->validatePayload($request, $agent, $agentUser);
    $agentUser = $agentUser->modifyFromAgent($payload);
    return AgentUserResource::make($agentUser);
  }

  public function createOTLoginLink(Request $request, User $agentUser): AgentOTLResource
  {
    $payload = $request->validate([
      'exit_url' => 'required|url',
    ]);
    $link = $agentUser->createOneTimeLoginLink($payload);
    return AgentOTLResource::make($link);
  }

  public function addHotel(User $agentUser, Hotel $agentHotel): array
  {
    $agentUser->agentHotels()->sync([$agentHotel->id], false);
    return ['ok' => true];
  }

  public function deleteHotel(User $agentUser, Hotel $agentHotel): array
  {
    $agentUser->agentHotels()->detach($agentHotel);
    return ['ok' => true];
  }

  public function setHotels(Request $request, User $agentUser): array
  {
    $agent = $this->agent();
    $data = $request->validate([
      'ids'   => 'required|array',
      'ids.*' => 'numeric',
    ]);
    $ids = Hotel::query()->where('agent_id', $agent->id)->whereIn('id', Arr::get($data, 'ids'))->get(['id'])->pluck('id');
    $agentUser->agentHotels()->sync($ids);
    return ['ok' => true];
  }

  public function clearHotels(User $agentUser): array
  {
    $agentUser->agentHotels()->sync([]);
    return ['ok' => true];
  }

}
