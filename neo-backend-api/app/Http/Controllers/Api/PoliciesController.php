<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\PMSManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PoliciesController extends Controller {

  private function cancelValidationRules($edit = false)
  {
    $rules = [];
    if ($edit) {
      $rules += ['id' => 'required|numeric'];
    }
    $rules += [
      'langs' => 'required|array',
    ];
    foreach (PMSManager::$langs as $lang) {
      $rules += [
        "langs.$lang"      => ($lang === 'en' ? 'required' : 'sometimes').'|array',
        "langs.$lang.name" => ($lang === 'en' ? 'required' : 'nullable').'|string|min:1|max:200',
        "langs.$lang.desc" => 'nullable|string|min:1|max:500',
      ];
    }
    $ams = PMSManager::allAmountModesList();
    $tus = PMSManager::allTimeUnitsList();
    $bts = PMSManager::allBasisTypesList();
    $dts = PMSManager::allDropTimesList();
    $rules += [
      'cancellationFee'                 => 'required|array',
      'cancellationFee.value'           => 'required|numeric',
      'cancellationFee.mode'            => 'required|in:'.$ams,
      'cancellationFee.nmbrOfNights'    => 'required|numeric',
      'cancellationFee.basisType'       => 'required|in:'.$bts,
      'cancellationTime'                => 'required|array',
      'cancellationTime.unitMultiplier' => 'required|numeric',
      'cancellationTime.timeUnit'       => 'required|in:'.$tus,
      'cancellationTime.dropTime'       => 'required|in:'.$dts,
    ];
    return $rules;
  }

  private function paymentValidationRules($edit = false)
  {
    $rules = [];
    if ($edit) {
      $rules += ['id' => 'required|numeric'];
    }
    $ams = PMSManager::allAmountModesList();
    $tus = PMSManager::allTimeUnitsList();
    $rules += [
      'name'                       => 'required',
      'desc'                       => 'required',
      'paymentType'                => 'required|numeric|in:'.implode(',', PMSManager::$defaultPaymentTypeIDs),
      'paymentFee'                 => 'required|array',
      'paymentFee.value'           => 'required|numeric',
      'paymentFee.mode'            => 'required|in:'.$ams,
      'paymentTime'                => 'required|array',
      'paymentTime.unitMultiplier' => 'required|numeric',
      'paymentTime.timeUnit'       => 'required|in:'.$tus,
    ];
    return $rules;
  }

  /**
   * Get policies list
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return Response|array
   */
  public function get(Request $request, PMSManager $manager)
  {
    $data = $manager->getPolicies();
    $bgarants = PMSManager::defaultBookingGuarantees();
    $pmts = PMSManager::defaultPaymentTypes();
    $data += compact('bgarants', 'pmts');
    return $data;
  }

  /**
   * Create cancel policy
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return array|bool|null
   * @throws ValidationException
   */
  public function createCancel(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->cancelValidationRules());
    $payload = $manager->purifyLangs($payload);
    $data = $manager->modifyCancelPolicy($payload);
    $bgarants = PMSManager::defaultBookingGuarantees();
    $pmts = PMSManager::defaultPaymentTypes();
    $data += compact('bgarants', 'pmts');
    return $data;
  }

  /**
   * Update cancel policy
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return array
   * @throws ValidationException
   */
  public function updateCancel(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->cancelValidationRules(true));
    $payload = $manager->purifyLangs($payload);
    $data = $manager->modifyCancelPolicy($payload);
    $bgarants = PMSManager::defaultBookingGuarantees();
    $pmts = PMSManager::defaultPaymentTypes();
    $data += compact('bgarants', 'pmts');
    return $data;
  }

  /**
   * Duplicate cancel policy
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param $id
   *
   * @return array|bool
   * @throws ValidationException
   */
  public function duplicateCancel(Request $request, PMSManager $manager, $id)
  {
    throw new NotFoundHttpException();
    $payload = $this->validate($request, $this->cancelValidationRules(true));
    $payload['text'] = '[COPY] '.$payload['text'];
//    $payload['_copy'] = true;
//    $payload['id'] = $id;
    Arr::forget($payload, 'id');
    $data = $manager->modifyCancelPolicy($payload);
    $bgarants = PMSManager::defaultBookingGuarantees();
    $pmts = PMSManager::defaultPaymentTypes();
    $data += compact('bgarants', 'pmts');
    return $data;
  }

  /**
   * Delete cancel policy
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param $id
   *
   * @return array
   */
  public function destroyCancel(Request $request, PMSManager $manager, $id)
  {
    $payload = [
      '_delete' => true,
      'id'      => $id,
    ];
    $data = $manager->modifyCancelPolicy($payload);
    return ['ok' => true];
  }

  /**
   * Create payment policy
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return array|bool|null
   * @throws ValidationException
   */
  public function createPayment(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->paymentValidationRules());
    $data = $manager->modifyPaymentPolicy($payload);
    $bgarants = PMSManager::defaultBookingGuarantees();
    $pmts = PMSManager::defaultPaymentTypes();
    $data += compact('bgarants', 'pmts');
    return $data;
  }

  /**
   * Update payment policy
   *
   * @param Request $request
   * @param PMSManager $manager
   *
   * @return array
   * @throws ValidationException
   */
  public function updatePayment(Request $request, PMSManager $manager)
  {
    $payload = $this->validate($request, $this->paymentValidationRules(true));
    $data = $manager->modifyPaymentPolicy($payload);
    $bgarants = PMSManager::defaultBookingGuarantees();
    $pmts = PMSManager::defaultPaymentTypes();
    $data += compact('bgarants', 'pmts');
    return $data;
  }

  /**
   * Duplicate payment policy
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param $id
   *
   * @return array|bool
   * @throws ValidationException
   */
  public function duplicatePayment(Request $request, PMSManager $manager, $id)
  {
    throw new NotFoundHttpException();
    $payload = $this->validate($request, $this->paymentValidationRules(true));
    $payload['name'] = '[COPY] '.$payload['name'];
//    $payload['_copy'] = true;
//    $payload['id'] = $id;
    Arr::forget($payload, 'id');
    $data = $manager->modifyPaymentPolicy($payload);
    $bgarants = PMSManager::defaultBookingGuarantees();
    $pmts = PMSManager::defaultPaymentTypes();
    $data += compact('bgarants', 'pmts');
    return $data;
  }

  /**
   * Delete payment policy
   *
   * @param Request $request
   * @param PMSManager $manager
   * @param $id
   *
   * @return array
   */
  public function destroyPayment(Request $request, PMSManager $manager, $id)
  {
    $payload = [
      '_delete' => true,
      'id'      => $id,
    ];
    $data = $manager->modifyPaymentPolicy($payload);
    return ['ok' => true];
  }
}
