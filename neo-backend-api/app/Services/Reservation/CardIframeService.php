<?php

namespace App\Services\Reservation;

class CardIframeService
{
  const SALT_PREFIX = 'cultuzz-';
  const CIPHER = 'AES-256-CBC';

  private function getSalt($objectId) {
    return self::SALT_PREFIX.sprintf("%'.08d", $objectId);
  }

  private function encryptData($objectId, $dataToEncrypt) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));
    $ciphertext_raw = openssl_encrypt($dataToEncrypt, self::CIPHER, $this->getSalt($objectId), OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, $this->getSalt($objectId), true);
    return urlencode(base64_encode( $iv.$hmac.$ciphertext_raw ));
  }

  public function getIframeUrl($data) {
    return sprintf(
      "%sbookingID=%s&objectID=%s&userID=%s&css=null",
      config('cultuzz.card_details_iframe_url'),
      $this->encryptData($data['objectId'], $data['bookingId']),
      $data['objectId'],
      $this->encryptData($data['objectId'], $data['userId']),
    );
  }


}
