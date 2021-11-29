<?php


namespace App\Services\Directus;


use App\Contracts\RestApiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

abstract class DirectusCollectionService implements RestApiService
{
  /**
   * @var string
   */
  protected string $directus_collection_url;

  /**
   * DirectusService constructor.
   */
  public function __construct()
  {
    $this->directus_collection_url = config('directus.collection_url');
  }

  /**
   * @param array $query
   * @return Collection
   */
  public function list(array $query = []): Collection
  {
    $response = Http::get(
      $this->getApiRequestUrl(),
      $query
    );

    if (!$response->ok()) {
      abort($response->status(), $response->body());
    }

    return Collection::make($response->json()['data']);
  }

  /**
   * @param int $id
   * @param array $query
   * @return Collection
   */
  public function show(int $id, array $query = []): Collection
  {
    $response = Http::get(
      $this->getApiRequestUrl()
        ->finish('/')
        ->append($id),
      $query
    );

    if (!$response->ok()) {
      abort($response->status(), $response->body());
    }

    return Collection::make($response->json()['data']);
  }

  /**
   * @param array $data
   * @return Collection
   */
  public function store(array $data): Collection
  {
    $response = Http::post(
      $this->getApiRequestUrl(),
      $data
    );

    if (!$response->successful()) {
      abort($response->status(), $response->body());
    }

    return Collection::make($response->json()['data']);
  }

  /**
   * @param int $id
   * @param array $data
   * @return Collection
   */
  public function update(int $id, array $data): Collection
  {
    $response = Http::patch(
      $this->getApiRequestUrl()
      ->finish('/')
      ->append($id),
      $data
    );

    if (!$response->successful()) {
      abort($response->status(), $response->body());
    }

    return Collection::make($response->json()['data']);
  }

  /**
   * @param int $id
   * @return bool
   */
  public function delete(int $id): bool
  {
    $response = Http::delete(
      $this->getApiRequestUrl()
        ->finish('/')
        ->append($id)
    );

    if (!$response->successful()) {
      abort($response->status(), $response->body());
    }

    return $response->successful();
  }

  /**
   * @return string
   */
  abstract protected function getCollectionName(): string;

  /**
   * @return Stringable
   */
  private function getApiRequestUrl(): Stringable
  {
    return Str::of($this->directus_collection_url)
      ->finish('/')
      ->append($this->getCollectionName());
  }
}
