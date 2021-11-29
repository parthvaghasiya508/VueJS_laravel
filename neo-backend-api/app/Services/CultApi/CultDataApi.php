<?php

namespace App\Services\CultApi;

use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CultDataApi
{

  public array $config = [];

  public string $baseUrl = '';

  public function __construct(
    array $config
  ) {
    $this->config   = $config;
    $this->baseUrl  = config('cultuzz.cultdata_api');
  }

  private function getHeaders() {
    return [
      'X-API-KEY'     => $this->config['xApiKey'],
      'Accept'        => 'application/json',
      'Content-Type'  => 'application/json',
    ];
  }

  protected function get($url) {
    $request = Http::withHeaders($this->getHeaders())->get($this->baseUrl.$url);
    if ($request->status() == Response::HTTP_OK) {
      return $request->json();
    }
    return null;
  }

  protected function post($url, $data) {
    return Http::withHeaders($this->getHeaders())->post($this->baseUrl.$url, $data)->json();
  }

  protected function patch($url, $data) {
    return Http::withHeaders($this->getHeaders())->patch($this->baseUrl.$url, $data)->json();
  }

}
