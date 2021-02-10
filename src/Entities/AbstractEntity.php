<?php

namespace Translation\Entities;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Class AbstractEntity.
 *
 * @package Core\Entities
 */
abstract class AbstractEntity implements Arrayable, JsonSerializable
{
    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * persist
     *
     * @return bool
     */
    private function persist()
    {
        $this->model::create($this->toArray());
    }

}
