<?php

namespace Translation\Entities;

use Translation\Models\Contry;

class ContryEntity extends AbstractEntity
{
    protected $model = Contry::class;

    private $code;
    private $name;
    private $icon;
    private $external = [
        'pointagram' => null,
    ];

    /**
     * ContryEntity constructor.
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
    private function setCode(int $code): ContryEntity
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
    public function getExternal(string $service): string
    {
        return $this->external[$service];
    }
    public function setExternal(string $service, $value): vocode
    {
        $this->external[$service] = $value;
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
