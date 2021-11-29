<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Class IntCollection
 * @package App\Support
 *
 */
class IntCollection extends Collection {

  /**
   * @param mixed $item
   *
   * @return self
   */
  public function add($item): self
  {
    $this->items[] = (int)$item;
    return $this;
  }

  /**
   * @param $item
   *
   * @return self
   */
  public function remove($item): self
  {
    $this->items = array_values(array_filter($this->items, fn ($i) => $i !== (int)$item));
    return $this;
  }

  /**
   * @return self
   */
  public function toInt(): self
  {
    foreach ($this->items as &$i) {
      $i = (int)$i;
    }
    return $this;
  }
}
