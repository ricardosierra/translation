<?php

namespace Translation\Entities;

use Translation\Models\Locale;

class LocaleEntity extends AbstractEntity
{
    protected $model = Locale::class;

    private $code;
    private $name;
    private $icon;

    /**
     * LocaleEntity constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        if (isset($attributes['code']) && !is_null($attributes['code'])) {
            $this->setCode($attributes['code']);
        }
        if (isset($attributes['name']) && !is_null($attributes['name'])) {
            $this->setName($attributes['name']);
        }
        if (isset($attributes['icon']) && !is_null($attributes['icon'])) {
            $this->setIcon($attributes['icon']);
        }
    }

    /**
     * @param  int $code
     * @return $this
     */
    private function setCode(int $code): LocaleEntity
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function setName($value): vocode
    {
        $this->name = $value;
    }
    public function getIcon(): string
    {
        return $this->icon;
    }
    public function setIcon($value): vocode
    {
        $this->icon = $value;
    }
    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'name' => $this->getName(),
        ];
    }
}
