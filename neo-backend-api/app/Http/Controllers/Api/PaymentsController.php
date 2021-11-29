<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\Cultuzz;
use App\Models\Hotel;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\CultApi\PaymentMethodApi;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\PaymentMethod;
use Stripe\Stripe;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PaymentsController extends Controller
{
  /**
   * @param Request $request
   * @return array
   */
  public function get(Request $request)
  {
    $creditorId = config('cultuzz.creditor_id');
    $hotel = session('hotel');
    $stripeUser = $hotel->createOrGetStripeCustomer([]);
    $hotel->updateDefaultPaymentMethodFromStripe();
    $intent = $hotel->createSetupIntent(['payment_method_types' => Cultuzz::PAYMENT_METHODS]);
    $paymentMethods = $hotel->paymentMethods()->map(function($paymentMethod){
      return $paymentMethod->asStripePaymentMethod();
    });
    if (!is_null($hotel->defaultPaymentMethod())) {
      $defaultPaymentMethod = $hotel->defaultPaymentMethod()->asStripePaymentMethod();
    } else {
      $defaultPaymentMethod = null;
    }

    return compact(
      'stripeUser',
      'intent',
      'paymentMethods',
      'defaultPaymentMethod',
      'creditorId'
    );
  }

  /**
   * @param Request $request
   * @return Application|ResponseFactory|Response
   */
  public function store(
    Request $request,
    PaymentMethodApi $paymentMethodApi
  ) {
    $validated = $request->validate([
      'updatePaymentMethod'           => 'boolean',
      'payment_method'                => 'required',
      'hasSepaCoreDirectDebitMandate' => 'boolean',
      'createPaymentInCultData'       => 'boolean'
    ]);
    $hotel = session('hotel');
    $validated['clientId'] = $this->hotel()->id;
    if ($validated['updatePaymentMethod']) {
      $newPaymentMethod = $hotel->updateDefaultPaymentMethod($validated['payment_method']);
    } else {
      $newPaymentMethod = $hotel->addPaymentMethod($validated['payment_method']);
    }
    if ($validated['createPaymentInCultData']) {
      $paymentMethodApi->save($newPaymentMethod->asStripePaymentMethod(), $validated);
    } else {
      $paymentMethodApi->update($newPaymentMethod->asStripePaymentMethod(), $validated);
    }

    return response([
      'paymentMethod' => $newPaymentMethod->asStripePaymentMethod(),
      'setupIntent' => session('hotel')->createSetupIntent(['payment_method_types' => Cultuzz::PAYMENT_METHODS]),
    ], 201);
  }

  public function purchase(Request $request)
  {
    $validated = $request->validate([
      'amount' => 'numeric|required',
      'payment_method' => 'required',
      'options' => 'sometimes'
    ]);

    try {
      $hotel = session('hotel');
      return $hotel->charge($validated['amount'], $validated['payment_method'], $validated['options']);
    } catch (Exception $e) {
      throw new BadRequestHttpException('Payment failed');
    }
  }

  /**
   * @param Request $request
   * @param PaymentMethod $paymentMethod
   * @return bool[]
   */
  public function delete(Request $request, PaymentMethod $paymentMethod)
  {
    // check if active subscription
    $hotel = session('hotel');
    if($hotel->subscriptions()->active()->count() > 0){
      throw new BadRequestHttpException('You have an active subscription');
    }else if($hotel->defaultPaymentMethod() == $paymentMethod){
      throw new BadRequestHttpException('This payment method used by default');
    }else{
      $paymentMethod->delete();
    }

    return ['ok' => true];
  }

  public function fetchPaymentMethod(
    Request $request,
    PaymentMethodApi $paymentMethodApi,
    $paymentMethod
  ) {
    return $paymentMethodApi->fetch($this->hotel()->id, $paymentMethod);
  }

}
