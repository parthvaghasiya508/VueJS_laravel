<?php

namespace App\Services\Directus;

use Illuminate\Support\Collection;

final class PageService extends DirectusCollectionService
{
  //These stubs generated for knowing that you can rewrite any methods and do any stuff with data that you want

  /**
   * @param array $query
   * @return Collection
   */
  public function list(array $query = []): Collection
  {
    return parent::list($query);
  }

  /**
   * @param int $id
   * @param array $query
   * @return Collection
   */
  public function show(int $id, array $query = []): Collection
  {
    return parent::show($id, $query);
  }

  /**
   * @param array $data
   * @return Collection
   */
  public function store(array $data): Collection
  {
    return parent::store($data);
  }

  /**
   * @param int $id
   * @param array $data
   * @return Collection
   */
  public function update(int $id, array $data): Collection
  {
    return parent::update($id, $data);
  }

  /**
   * @param int $id
   * @return bool
   */
  public function delete(int $id): bool
  {
    return parent::delete($id);
  }

  /**
   * @return string
   */
  protected function getCollectionName(): string
  {
    return 'legal';
  }
}
