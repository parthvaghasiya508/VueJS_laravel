<?php

namespace App\Managers;

use GuzzleHttp\Client;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class CDManager {

  protected Client $client;

  protected string $apiUrl = 'https://api.cultdata.com/api/';

  public function __construct()
  {
    $this->client = new Client([
      'base_uri' => $this->apiUrl,
      'headers'  => [
        'Accept'    => 'application/json',
        'X-API-KEY' => config('cultuzz.cultdata_key'),
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

  public function getInvoices(int $client_id, int $page = 1, int $limit = 100): array
  {
    return $this->response($this->client->get('invoices', [
      'query' => compact('client_id', 'page', 'limit'),
    ]));
  }

  public function getReports(int $client_id, int $page = 1, int $limit = 100): array
  {
    return $this->response($this->client->get('reports', [
      'query' => compact('client_id', 'page', 'limit'),
    ]));
  }

  public function getDashboard(int $client_id)
  {
    return $this->response($this->client->get('extranet/report/'.$client_id));
  }

  public function getSettings()
  {
    return $this->response($this->client->get('extranet/settings/'));
  }

}
