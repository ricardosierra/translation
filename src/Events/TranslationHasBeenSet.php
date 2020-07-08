<?php

namespace Translation\Events;

class TranslationHasBeenSet
{
    /** @var \Translation\Translatable */
    public $model;

    /** @var string */
    public $key;

    /** @var string */
    public $locale;

    public $oldValue;
    public $newValue;

    public function __construct($model, string $key, string $locale, $oldValue, $newValue)
    {
        $this->model = $model;

        $this->key = $key;

        $this->locale = $locale;

        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }
}