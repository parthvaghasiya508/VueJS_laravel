<?php

namespace App\Services\RoomDB;

use App\Models\Hotel;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\SimpleCache\InvalidArgumentException;

class RoomDBService {

  const TOKEN_CACHE_KEY = 'roomdb_api.token';

  /**
   * @var string
   */
  protected string $url;

  /**
   * @var string
   */
  protected string $supplierId;

  /**
   * @var string
   */
  protected string $supplierSecret;

  /**
   * @var string
   */
  protected string $token;

  /**
   * @var Client
   */
  protected Client $client;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->url = config('roomdb.base_url');
    $this->supplierId = config('roomdb.supplier_id');
    $this->supplierSecret = config('roomdb.supplier_secret');

    $this->client = $this->getHttpClient();
    try {
      $this->getToken();
    } catch (GuzzleException $e) {
      Log::error("[RoomDB] Can not initialize client. RoomDB api error.", ['exception' => $e]);
    } catch (InvalidArgumentException $e) {
      Log::error("[RoomDB] Can not initialize client. Invalid argument. ", ['exception' => $e]);
    }

  }

  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function getPropertyTypes(): array
  {
    $response = $this->client->get(
      'property-types'
    );

    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @param array $data
   *
   * @return array
   * @throws GuzzleException
   */
  public function createProperty(array $data): array
  {
    try {
      $response = $this->client->post(
        'properties',
        [
          'json' => $data,
        ]
      );
    } catch (ClientException $exception) {
      Log::error("RoomDB error response (input data)", [
        'data' => $data,
      ]);

      $request = $exception->getRequest();
      $body = $request->getBody();
      $body->rewind();
      $contents = $body->getContents();

      Log::error("Request: ", ['contents' => $contents]);
      $response = $exception->getResponse();
    }

    return [
      'status' => $response->getStatusCode(),
      'data'   => json_decode($response->getBody()->getContents(), true),
    ];
  }

  /**
   * @return Client
   */
  protected function getHttpClient(): Client
  {
    // no need for other middlewares, so we're not using HandlerStack::create() here
    $stack = new HandlerStack(Utils::chooseHandler());

    // intercept 401 responses and try to renew token
    $stack->push(Middleware::retry(function (
      $retries,
      Request $request,
      Response $response = null,
      RequestException $exception = null
    ) {
      if ($retries >= 1) {
        return false;
      }
      if ($response && $response->getStatusCode() === 401) {
        $this->getToken(true);
        return true;
      }
      return false;
    }, fn ($retries) => 250));

    // add access token to all requests except `suppliers/get-token` itself
    $stack->push(Middleware::mapRequest(fn (RequestInterface $request) => Str::endsWith($request->getUri()->getPath(), '/get-token')
      ? $request
      : $request->withHeader('Authorization', 'Bearer '.$this->token)
    ));

    // useful middleware
    $stack->push(Middleware::prepareBody(), 'prepare_body');

    return new Client([
      'handler'  => $stack,
      'base_uri' => $this->url,
      'headers'  => [
        'Accept' => 'application/json',
      ]
    ]);
  }

  /**
   * @throws GuzzleException
   * @throws InvalidArgumentException
   */
  public function getToken($renew = false)
  {

    if (!$renew && cache()->has(static::TOKEN_CACHE_KEY)) {
      $this->token = cache()->get(static::TOKEN_CACHE_KEY);
      return;
    }

    try {
      $tokenResponse = $this->client
        ->post('suppliers/get-token', [
          'multipart' => [
            [
              'name'     => 'supplierId',
              'contents' => $this->supplierId,
            ],
            [
              'name'     => 'supplierSecret',
              'contents' => $this->supplierSecret,
            ],
          ],
        ]);

      $tokenResponseJSON = json_decode($tokenResponse->getBody()->getContents(), true);
      $token = $tokenResponseJSON['accessToken'];
      $expiresIn = $tokenResponseJSON['expiresIn'];

      $this->token = $token;

      cache()->put(static::TOKEN_CACHE_KEY, $this->token,
        Carbon::createFromTimestamp($expiresIn)->subMinute()
      );
    } catch (ClientException $exception) {
      cache()->delete(static::TOKEN_CACHE_KEY);

      throw $exception;
    }
  }

  /**
   * @param string $langCode
   *
   * @return array
   * @throws GuzzleException
   */
  public function getCountries(string $langCode = 'en'): array
  {
    try {
      $response = $this->client->get(
        'countries',
        [
          'json' => ['langCode' => $langCode],
        ]
      );
    } catch (ClientException $exception) {
      $request = $exception->getRequest();
      $body = $request->getBody();
      $body->rewind();
      $contents = $body->getContents();
      Log::error("Request: ", ['contents' => $contents]);
      $response = $exception->getResponse();
    }

    return [
      'status'       => $response->getStatusCode(),
      'country_list' => json_decode($response->getBody()->getContents(), true),
    ];
  }

  /**
   * @param string $iso
   *
   * @return array
   * @throws GuzzleException
   */
  public function getStatesFromCountry(string $iso): array
  {
    try {
      $response = $this->client->get(
        'states/by-country-code/'.$iso
      );
    } catch (ClientException $exception) {
      $request = $exception->getRequest();
      $body = $request->getBody();
      $body->rewind();
      $contents = $body->getContents();
      Log::error("Request: ", ['contents' => $contents]);
      $response = $exception->getResponse();
    }

    return [
      'status'     => $response->getStatusCode(),
      'state_list' => json_decode($response->getBody()->getContents(), true),
    ];
  }

  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function getLanguages(): array
  {
    try {
      $response = $this->client->get(
        'languages'
      );
      return json_decode($response->getBody()->getContents(), true);
    }
    catch (GuzzleException $e) {
      Log::error("Get Languages: ", ['error' => $e->getMessage()]);
    }
  }
  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function getCurrencies(): array
  {
    $response = $this->client->get(
      'currencies'
    );

    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function getDescriptionTypes(): array
  {
    try {
      $response = $this->client->get(
        'description/description-types'
      );
    } catch (ClientException $exception) {
      Log::error("RoomDB error response (input data)", [
        'data' => $exception->getMessage(),
      ]);

      $request = $exception->getRequest();
      $body = $request->getBody();
      $body->rewind();
      $contents = $body->getContents();

      Log::error("Request: ", ['contents' => $contents]);
      $response = $exception->getResponse();
    }
    return [
      'status' => $response->getStatusCode(),
      'data'   => json_decode($response->getBody()->getContents(), true),
    ];
  }

  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function updatePropertyDescription($propertyId,$data): array
  {
    try {
      if($data['requestType'] == 'POST'){
        $response = $this->client->post(
          "properties/description/{$propertyId}",
          [
            'json' => $data,
          ]
        );
      }else{
        $response = $this->client->patch(
          "properties/description/{$data['decriptionId']}",
          [
            'json' => $data,
          ]
        );
      }

    } catch (ClientException $exception) {
      Log::error("RoomDB error response (input data)", [
        'data' => $data,
      ]);

      $request = $exception->getRequest();
      $body = $request->getBody();
      $body->rewind();
      $contents = $body->getContents();

      Log::error("Request: ", ['contents' => $contents]);
      $response = $exception->getResponse();
    }
    return [
      'status' => $response->getStatusCode(),
      'data'   => json_decode($response->getBody()->getContents(), true),
    ];
  }

  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function deleteDescription($decriptionId): array
  {
    try {
      $response = $this->client->delete(
        "properties/description/{$decriptionId}",
      );

    } catch (ClientException $exception) {
      Log::error("RoomDB error response (input data)", [
        'data' => $exception->getMessage(),
      ]);

      $request = $exception->getRequest();
      $body = $request->getBody();
      $body->rewind();
      $contents = $body->getContents();

      Log::error("Request: ", ['contents' => $contents]);
      $response = $exception->getResponse();
    }
    return [
      'status' => $response->getStatusCode(),
      'data'   => json_decode($response->getBody()->getContents(), true),
    ];

  }

  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function getPropertiesList(): array
  {
    try {
      $response = $this->client->get(
        'properties'
      );
    } catch (ClientException $exception) {
      Log::error("RoomDB error response (input data)", [
        'data' => $exception->getMessage(),
      ]);

      $request = $exception->getRequest();
      $body = $request->getBody();
      $body->rewind();
      $contents = $body->getContents();

      Log::error("Request: ", ['contents' => $contents]);
      $response = $exception->getResponse();
    }
    return [
      'status' => $response->getStatusCode(),
      'data'   => json_decode($response->getBody()->getContents(), true),
    ];
  }

  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function getProperty($id): array
  {
    try {
      $response = $this->client->get(
        "properties/{$id}"
      );
    } catch (ClientException $exception) {
      Log::error("RoomDB error response (input data)", [
        'data' => $exception->getMessage(),
      ]);

      $request = $exception->getRequest();
      $body = $request->getBody();
      $body->rewind();
      $contents = $body->getContents();

      Log::error("Request: ", ['contents' => $contents]);
      $response = $exception->getResponse();
    }
    return [
      'status' => $response->getStatusCode(),
      'data'   => json_decode($response->getBody()->getContents(), true),
    ];
  }

  /*
   * @throws GuzzleException
   */
  public function getPropertyDataBySupplierId(int $id): array
  {
      $response = $this->client->get("properties/supplier-property-id/${id}");
      return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @throws GuzzleException
   */
  public function getIdentifierSources(): array
  {
      $response = $this->client->get('identifier-source');
      return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @throws GuzzleException
   */
  public function getPropertyIdentifiers(int $propertyId): array
  {
      $response = $this->client->get("properties/identifier/{$propertyId}");
      return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @throws GuzzleException
   */
  public function updatePropertyIdentifiers(array $json): array
  {
      $response = $this->client->post('properties/identifier', compact('json'));
      return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @throws GuzzleException
   */
  public function deletePropertyIdentifier(int $propertyId, int $sourceId): array
  {
      $response = $this->client->delete("properties/identifier/{$propertyId}/{$sourceId}");
      return json_decode($response->getBody()->getContents(), true);
  }
  /**
   * @param Hotel|null $hotel
   * @return array|null
   */
  public function getPropertyByHotel(?Hotel $hotel): ?array {
    try {
      $response = $this->client->get(
        'properties/'.($hotel->roomdb_id ? $hotel->roomdb_id : 0)
      );
    }
    catch (GuzzleException $e) {
      Log::error("Get property: ", ['error' => $e->getMessage()]);
      $response = $e->getResponse();
    }

    return [
      'status'    => $response->getStatusCode(),
      'property'  => json_decode($response->getBody()->getContents(), true),
    ];
  }

  /**
   * @param Hotel|null $hotel
   * @param string $currency
   */
  public function updatePropertyCurrency(?Hotel $hotel, string $currency) {
    try {
      if ($hotel->roomdb_id) {
        $this->client->patch(
          'properties/home-currency/by-code',
          [
            'json' => [
              'propertyId'        => $hotel->roomdb_id,
              'homeCurrencyCode'  => $currency
            ]
          ]
        );
      }
    }
    catch (GuzzleException $e) {
      Log::error("Update property currency by code: ", ['error' => $e->getMessage()]);
    }
  }

}
