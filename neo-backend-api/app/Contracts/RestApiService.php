<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface RestApiService
{
  /**
   * @param array $query
   * @return Collection
   */
  public function list(array $query = []): Collection;

  /**
   * @param int $id
   * @return Collection
   */
  public function show(int $id): Collection;

  /**
   * @param array $data
   * @return Collection
   */
  public function store(array $data): Collection;

  /**
   * @param int $id
   * @param array $data
   * @return Collection
   */
  public function update(int $id, array $data): Collection;

  /**
   * @param int $id;
   * @return bool
   */
  public function delete(int $id): bool;
}
