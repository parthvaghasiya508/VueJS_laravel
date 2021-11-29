<?php

namespace App\Services\CultApi;

use Illuminate\Support\Facades\Http;

Class SettingsApi
{
  public string $baseUrl = '';

  public function __construct()
  {
    $this->baseUrl = config('cultuzz.endpoint_setting');
  }

  private function linkGenerator(String $method, String $object, Array $parameters=null)
  {
    $fullLink = $this->baseUrl . $object;
    if ($method == 'GET') {
      $parameterFinal = $parameters;
      if (is_array($parameterFinal)) {
        $fullLink = $fullLink . '?';
        foreach ($parameterFinal as $key => $param) {
          $fullLink = $fullLink . $key . '=' . $param  . '&' ;
        }
      }
    }
    return $fullLink;
  }

  public function get(String $method, String $object, Array $parameters)
  {
    return $this->sendToApi($method, $object, $parameters);
  }

  public function post(String $method, String $object, Array $parameters)
  {
    return $this->sendToApi($method, $object, $parameters);
  }

  public function put(String $method, String $object, Array $parameters)
  {
    return $this->sendToApi($method, $object, $parameters);
  }

  private function sendToApi(String $method, String $object, Array $parameters)
  {
    if ($method == 'GET') {
      $link = $this->linkGenerator($method, $object, $parameters);
      $response = HTTP::get($link);
    } else if ($method == 'POST') { 
      $link = $this->linkGenerator($method, $object, null);
      $response = HTTP::post($link, $parameters);
    } else {
      $link = $this->linkGenerator($method, $object, null);
      $response = HTTP::put($link, $parameters);
    }
    return $response;
  }
}
