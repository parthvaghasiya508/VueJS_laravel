<?php

namespace App\Managers;

use GuzzleHttp\Client;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class CUManager {

  protected Client $client;

  protected string $apiUrl = 'http://api.extranet.cultuzz.com/api/';

  public function __construct()
  {
    $this->client = new Client([
      'base_uri' => $this->apiUrl,
      'headers'  => [
        'Accept'    => 'application/text',
      ],
    ]);
  }

  private function response(ResponseInterface $stream): ?array
  {
    try {
      return json_decode($stream->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
      return null;
    }
  }

  public function updateWidgets($widgets)
  {
    return $this->response($this->client->put('widget', [
      'query' => compact('widgets'),
    ]));
  }

}
