<?php

namespace App\Services\CultApi;

use App\Lib\Cultuzz;


class PaymentMethodApi extends CultDataApi
{
  const BASE_URI = 'payment-method';

  public function __construct(
  ) {
    $this->config = [
      'xApiKey' => config('cultuzz.cultdata_payment_key')
    ];
    parent::__construct($this->config);
  }

  public function fetch($userId, $paymentMethod)
  {
    return $this->get(
      sprintf('%s/%s/%d',
        self::BASE_URI,
        $this->getPartialUriByPaymentType($paymentMethod),
        $userId
      )
    );
  }

  public function save($paymentMethod, $formInput)
  {
    return $this->post(
      sprintf('%s/%s',
        self::BASE_URI,
        $this->getPartialUriByPaymentType($paymentMethod->type),
      ), $this->requestParams($paymentMethod, $formInput)
    );
  }

  public function update($paymentMethod, $formInput)
  {
    $params = $this->requestParams($paymentMethod, $formInput);
    unset($params['client']);
    return $this->patch(
      sprintf('%s/%s/%d',
        self::BASE_URI,
        $this->getPartialUriByPaymentType($paymentMethod->type),
        $formInput['clientId']
      ), $params
    );
  }

  private function getPartialUriByPaymentType($paymentType)
  {
    switch ($paymentType) {
      case Cultuzz::PAYMENT_SEPA:
        return 'sepa';
      case Cultuzz::PAYMENT_CARD:
        return 'credit-card';
      default:
        return null;
    }
  }

  private function requestParams($paymentMethod, $formInput)
  {
    switch ($paymentMethod->type) {
      case Cultuzz::PAYMENT_SEPA:
        return [
          'hasSepaCoreDirectDebitMandate' => $formInput['hasSepaCoreDirectDebitMandate'] ?? false,
          'iban'                          => $paymentMethod->sepa_debit->country.$paymentMethod->sepa_debit->bank_code,
          'accountHolder'                 => $paymentMethod->billing_details->email,
          'client'                        => $formInput['clientId']
        ];
      case Cultuzz::PAYMENT_CARD:
        return [
          'cardHolder'      => $paymentMethod->billing_details->name,
          'cardBrand'       => $paymentMethod->card->brand,
          'cardNumber'      => $paymentMethod->card->last4,
          'expirationMonth' => $paymentMethod->card->exp_month,
          'expirationYear'  => $paymentMethod->card->exp_year,
          'client'          => $formInput['clientId']
        ];
      default:
        return null;
    }
  }
}
