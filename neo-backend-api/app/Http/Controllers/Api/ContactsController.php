<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ContactsController extends Controller {

  private function validationRules($edit = false)
  {
    $rules = [
      'position'   => 'required|string',
      'salutation' => 'required|string',
      'language'   => 'required|string',
      'surname'    => 'required|string',
      'firstname'  => 'required|string',
      'mail'       => 'required|email',
      'phone'      => 'required|regex:/^\+\d{8,15}$/',
    ];
    if ($edit) {
      $rules += [
        'id'   => 'required|numeric',
        'type' => 'required|numeric',
      ];
    }
    return $rules;
  }

  /**
   * Get contact persons with additional info
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    return $manager->getContactPersons();
  }

  /**
   * Create contact person
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @throws ValidationException
   */
  public function create(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->validationRules());
    $manager->modifyContactPerson($payload);
    return $manager->getContactPersons();
  }

  /**
   * Update contact person
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @throws ValidationException
   */
  public function update(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->validationRules(true));
    $manager->modifyContactPerson($payload);
    return ['ok' => true];
  }

  /**
   * Delete contact person
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param int $id
   *
   * @return Response|array
   */
  public function destroy(Request $request, PMSManager $manager, $id)
  {
    $payload = [
      '_delete' => true,
      'id'      => $id,
    ];
    $manager->modifyContactPerson($payload);
    return ['ok' => true];
  }
}
