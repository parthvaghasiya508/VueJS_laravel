<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StripeUserRequest;
use App\Models\User;
use http\Env\Request;
use Laravel\Cashier\PaymentMethod;

class UserStripeController extends Controller {

  public function get(Request $request)
  {
    return response($request->user()->createOrGetStripeCustomer(), 200);
  }

  public function store(StripeUserRequest $request)
  {
    return $request->user()->createOrGetStripeCustomer($request->validated());
  }

  /**
   * @param StripeUserRequest $request
   * @param User $user
   * @param PaymentMethod $paymentMethod
   * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
   */
  public function update(StripeUserRequest $request)
  {
    return $request->user()->updateStripeCustomer($request->validated());
  }
}
