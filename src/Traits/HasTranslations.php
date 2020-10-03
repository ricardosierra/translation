<?php

namespace Translation\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Translation\Events\TranslationHasBeenSet;
use Translation\Exceptions\AttributeIsNotTranslatable;
use Translation\Repositories\ModelTranslationRepository;
use Translation\Services\GoogleTranslate;

/**
 * Model has Translations
 *
 * Usage: Add in model
 *  use HasTranslations;
 *
 *  public $translatable = ['name'];
 */
trait HasTranslations
{
    use Translatable;
    // @todo retirado tava dando pau, veio do cms
    // protected $appends = [
    //     'translations',
    // ];

    /**
     * From Siravel

     */

    // /**
    //  * Get a translation.
    //  *
    //  * @param string $lang
    //  *
    //  * @return mixed
    //  */
    // public function translation($lang)
    // {
    //     return app(ModelTranslationRepository::class)->getTranslation($this->id, get_class($this), $lang);
    // }

    /**
     * Get translation data.
     *
     * @param string $lang
     *
     * @return array|null
     */
    public function translationData($lang)
    {
        $translation = $this->translation($lang);

        if ($translation) {
            return json_decode($translation->entity_data);
        }

        return null;
    }
    
    /**
     * Peguei da Spatie
     */
    public function getAttributeValue($key)
    {
        if (! $this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        }

        return $this->getTranslation($key, $this->getLocale());
    }

    public function setAttribute($key, $value)
    {
        // Pass arrays and untranslatable attributes to the parent method.
        if (! $this->isTranslatableAttribute($key) || is_array($value)) {
            return parent::setAttribute($key, $value);
        }

        // If the attribute is translatable and not already translated, set a
        // translation for the current app locale.
        return $this->setTranslation($key, $this->getLocale(), $value);
    }

    public function translate(string $key, string $locale = '', bool $useFallbackLocale = true): string
    {
        return $this->getTranslation($key, $locale, $useFallbackLocale);
    }

    public function getTranslation(string $key, string $locale, bool $useFallbackLocale = true)
    {
        $locale = $this->normalizeLocale($key, $locale, $useFallbackLocale);

        $translations = $this->getTranslations($key);

        $translation = $translations[$locale] ?? '';

        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $translation);
        }

        return $translation;
    }

    public function getTranslationWithFallback(string $key, string $locale): string
    {
        return $this->getTranslation($key, $locale, true);
    }

    public function getTranslationWithoutFallback(string $key, string $locale)
    {
        return $this->getTranslation($key, $locale, false);
    }
    public function getTranslatedAttribute(string $key = null): string
    {
        return $this->getTranslation($key, $this->getLocale());
    }
    public function getTranslations(string $key = null): array
    {
        if ($key !== null) {
            $this->guardAgainstNonTranslatableAttribute($key);

            return array_filter(
                json_decode($this->getAttributes()[$key] ?? '' ?: '{}', true) ?: [],
                function ($value) {
                    return $value !== null && $value !== '';
                }
            );
        }

        return array_reduce(
            $this->getTranslatableAttributes(),
            function ($result, $item) {
                $result[$item] = $this->getTranslations($item);

                return $result;
            }
        );
    }

    public function setTranslation(string $key, string $locale, $value): HasTranslations
    {
        $this->guardAgainstNonTranslatableAttribute($key);

        $translations = $this->getTranslations($key);

        $oldValue = $translations[$locale] ?? '';

        if ($this->hasSetMutator($key)) {
            $method = 'set'.Str::studly($key).'Attribute';

            $this->{$method}($value, $locale);

            $value = $this->attributes[$key];
        }

        $translations[$locale] = $value;

        $this->attributes[$key] = $this->asJson($translations);

        event(new TranslationHasBeenSet($this, $key, $locale, $oldValue, $value));

        return $this;
    }

    public function setTranslations(string $key, array $translations): HasTranslations
    {
        $this->guardAgainstNonTranslatableAttribute($key);

        foreach ($translations as $locale => $translation) {
            $this->setTranslation($key, $locale, $translation);
        }

        return $this;
    }

    public function forgetTranslation(string $key, string $locale): HasTranslations
    {
        $translations = $this->getTranslations($key);

        unset(
            $translations[$locale],
            $this->$key
        );

        $this->setTranslations($key, $translations);

        return $this;
    }

    public function forgetAllTranslations(string $locale): HasTranslations
    {
        collect($this->getTranslatableAttributes())->each(
            function (string $attribute) use ($locale) {
                $this->forgetTranslation($attribute, $locale);
            }
        );

        return $this;
    }

    public function getTranslatedLocales(string $key): array
    {
        return array_keys($this->getTranslations($key));
    }

    public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->getTranslatableAttributes());
    }

    public function hasTranslation(string $key, string $locale = null): bool
    {
        $locale = $locale ?: $this->getLocale();

        return isset($this->getTranslations($key)[$locale]);
    }

    protected function guardAgainstNonTranslatableAttribute(string $key)
    {
        if (! $this->isTranslatableAttribute($key)) {
            throw AttributeIsNotTranslatable::make($key, $this);
        }
    }

    protected function normalizeLocale(string $key, string $locale, bool $useFallbackLocale): string
    {
        if (in_array($locale, $this->getTranslatedLocales($key))) {
            return $locale;
        }

        if (! $useFallbackLocale) {
            return $locale;
        }

        if (! is_null($fallbackLocale = config('translatable.fallback_locale'))) {
            return $fallbackLocale;
        }

        if (! is_null($fallbackLocale = config('app.fallback_locale'))) {
            return $fallbackLocale;
        }

        return $locale;
    }

    protected function getLocale(): string
    {
        return config('app.locale');
    }

    public function getTranslatableAttributes(): array
    {
        if (!property_exists($this, 'translatable')) {
            return [];
        }
        return is_array($this->translatable)
            ? $this->translatable
            : [];
    }


    /**
     * Get a translations attribute.
     *
     * @return array
     */
    public function getTranslationsAttribute(): array
    {
        return collect($this->getTranslatableAttributes())
            ->mapWithKeys(
                function (string $key) {
                    return [$key => $this->getTranslations($key)];
                }
            )
            ->toArray();
    }

    /**
     * Veio do Siravel @todo
     *
     * @return array
     * public function getTranslationsAttribute()
     * {
     *     $translationData = [];
     *     $translations = ModelTranslation::where('entity_id', $this->id)->where('entity_type', get_class($this))->get();
     *
     *     foreach ($translations as $translation) {
     *         $translationData[] = $translation->data->attributes;
     *     }
     *
     *     return $translationData;
     * }
     */

    public function getCasts(): array
    {
        return array_merge(
            parent::getCasts(),
            array_fill_keys($this->getTranslatableAttributes(), 'array')
        );
    }


    /**
     * Get a model as a translatable object (From CMS)
     *
     * @return Object
     */
    public function asObject()
    {
        if (! is_null(request('lang')) && request('lang') !== config('cms.default-language', 'en')) {
            return $this->translationData(request('lang'));
        }

        return $this;
    }
}
