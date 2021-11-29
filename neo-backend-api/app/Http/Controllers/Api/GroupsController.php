<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupInfoResource;
use App\Http\Resources\GroupResource;
use App\Models\Agent;
use App\Models\Group;
use App\Models\Page;
use App\Rules\Domain as DomainRule;
use App\Support\Domain;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GroupsController extends Controller {

  private function validationRules($id = null): array
  {
    $uniq = Rule::unique('groups');
    $ret = [
      'name'        => 'required|min:2|max:80',
      'logo'        => 'sometimes|array',
      'logo.remove' => 'boolean',
      'logo.upload' => 'nullable|file|mimetypes:image/png,image/jpeg',
      'pages.*'     => 'sometimes|exists:pages,name',
      'style'       => 'max:2000',
      'config'      => 'array|max:2000',
      'b_domain'    => ['nullable', new DomainRule(2), $uniq], // should be in array form
      'e_domain'    => ['required', 'different:b_domain', new DomainRule(2), $uniq], // should be in array form
    ];
    if ($id) {
      /** @var Group $group */
      $group = Group::find($id);
      $uniq->ignore($id);
      if ($group->domains_locked) {
        Arr::forget($ret, ['b_domain', 'e_domain']);
      }
    }
    $errors = [
      'b_domain.unique' => __('validation.domain_taken'),
      'e_domain.unique' => __('validation.domain_taken'),
    ];
    return [$ret, $errors];
  }

  /**
   * @param Request $request
   *
   * @return array
   */
  public function index(Request $request)
  {
    $list = Group::query()->with('owner', 'pages', 'agent')->get();
    $list->each(fn (Group $g) => $g->withData());
    $groups = GroupResource::collection($list);
    $pages = Page::allNames();
    $agents = Agent::all();
    return compact('groups', 'pages', 'agents');
  }

  /**
   * @param Request $request
   * @param Group $group
   *
   * @return GroupResource
   */
  public function show(Request $request, Group $group): ?GroupResource
  {
    $user = $this->user($request);
    $asAdmin = $group && $group->id && $user->admin;
    if (!$asAdmin) {
      $group = $this->group(); // Work to fix this.
      if (!$group) {
        abort(400, 'No group');
      }
    }
    $group->loadMissing('hotels');
    return GroupResource::make($group);
  }

  /**
   *
   * @param Request $request
   *
   * @return GroupResource
   * @throws ValidationException
   */
  public function store(Request $request): GroupResource
  {
    $user = $this->user($request);
    $payload = $this->validate($request, ...$this->validationRules());
    $group = Group::create($payload, $user);
    return GroupResource::make($group->withData(false));
  }

  /**
   * @param Request $request
   * @param Group $group
   *
   * @return GroupResource
   * @throws ValidationException
   */
  public function update(Request $request, Group $group): GroupResource
  {
    $user = $this->user($request);
    if (!$user->admin && !$user->belongsToGroup($group)) {
      abort(403, 'Access Denied');
    }
    $payload = $this->validate($request, ...$this->validationRules($group->id));
    return GroupResource::make($group->modify($payload, $user)->withData($user->admin));
  }

  /**
   * @param Request $request
   * @param Group $group
   *
   * @return array
   * @throws Exception
   */
  public function destroy(Request $request, Group $group): array
  {
    $user = $this->user($request);
    if ($group->user_id !== $user->id) {
      abort(403, 'Access Denied');
    }
    $group->delete();
    return ['ok' => 1];
  }

  public function domainInfo(Request $request)
  {
    $domain = $request->attributes->get('domain');
    $group = Group::findByLockedDomain($domain);
    if (!$group) {
      return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
    return GroupInfoResource::make($group);
  }

  public function checkDomain(Group $group)
  {
    $status = Domain::validateDNS($group);
    $group->domains_status = $status;
    $group->save();
    return GroupResource::make($group->withData());
  }

  public function lockDomain(Group $group)
  {
    $group->domains_locked = true;
    $group->save();
    return GroupResource::make($group->withData());
  }

  public function unlockDomain(Group $group)
  {
    $group->domains_locked = false;
    $group->save();
    return GroupResource::make($group->withData());
  }

  public function assignAgent(Group $group, Agent $agent)
  {
    if ($group->agent || $agent->group) {
      abort(403, 'Denied');
    }
    $agent->group()->associate($group)->save();
    return ['ok' => true];
  }

  public function unassignAgent(Group $group)
  {
    if (!$group->agent) {
      abort(403, 'Denied');
    }
    $group->agent->group()->dissociate()->save();
    return ['ok' => true];
  }
}
