<?php

namespace Translation\Traits;

use App\Models\SiravelModel;
use Translation\Models\Translation;
use Translation\Models\ModelTranslation;
use Translation\Repositories\TranslationRepository;
use App\Services\CmsService;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Log;
use Translation\GoogleTranslate;

trait Translatable
{
    /**
     * Get a translation.
     *
     * @param string $lang
     *
     * @return mixed
     */
    public function translation($lang)
    {
        return ModelTranslation::where('entity_id', $this->id)
            ->where('entity_type', get_class($this))
            ->where('entity_data', 'LIKE', '%"lang":"'.$lang.'"%')
            ->first();
    }

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
     * Get a translations attribute.
     *
     * @return array
     */
    public function getTranslationsAttribute(): array
    {
        $translationData = [];
        $translations = ModelTranslation::where('entity_id', $this->id)->where('entity_type', get_class($this))->get();

        foreach ($translations as $translation) {
            $translationData[] = $translation->data->attributes;
        }

        return $translationData;
    }
    /**
     * COmentado .. Essa funcao é a equivalente do Spatie @todo
     *
     * @return array
     */
    // public function getTranslationsAttribute(): array
    // {
    //     return collect($this->getTranslatableAttributes())
    //         ->mapWithKeys(function (string $key) {
    //             return [$key => $this->getTranslations($key)];
    //         })
    //         ->toArray();
    // }

    /**
     * After the item is created in the database.
     *
     * @param object $payload
     */
    public function afterCreate($payload)
    {
        if (config('cms.auto-translate', false)) {
            $entry = $payload->toArray();

            unset($entry['created_at']);
            unset($entry['updated_at']);
            unset($entry['translations']);
            unset($entry['is_published']);
            unset($entry['published_at']);
            unset($entry['id']);

            foreach (config('cms.languages') as $code => $language) {
                if ($code != config('cms.default-language')) {
                    $tr = new GoogleTranslate(config('cms.default-language'), $code);
                    $translation = [
                        'lang' => $code,
                        'template' => 'show',
                    ];

                    foreach ($entry as $key => $value) {
                        if (!empty($value)) {
                            try {
                                $translation[$key] = json_decode(json_encode($tr->translate(strip_tags($value))));
                            } catch (Exception $e) {
                                Log::info('[Translate] Erro> '.$e->getMessage());
                                unset($translation[$key]);
                            }
                        }
                    }

                    if (isset($translation['url'])) {
                        $translation['url'] = app(CmsService::class)->convertToURL($translation['url']);
                    }

                    $entityId = $payload->id;
                    $entityType = get_class($payload);
                    app(TranslationRepository::class)->createOrUpdate($entityId, $entityType, $code, $translation);
                }
            }
        }
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

    public function translate(string $key, string $locale = ''): string
    {
        return $this->getTranslation($key, $locale);
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

    public function getTranslations(string $key = null) : array
    {
        if ($key !== null) {
            $this->guardAgainstNonTranslatableAttribute($key);

            return array_filter(
                json_decode($this->getAttributes()[$key] ?? '' ?: '{}', true) ?: [], function ($value) {
                    return $value !== null && $value !== '';
                }
            );
        }

        return array_reduce(
            $this->getTranslatableAttributes(), function ($result, $item) {
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

        unset($translations[$locale]);

        $this->setAttribute($key, $translations);

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

    public function getTranslatedLocales(string $key) : array
    {
        return array_keys($this->getTranslations($key));
    }

    public function isTranslatableAttribute(string $key) : bool
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

    protected function normalizeLocale(string $key, string $locale, bool $useFallbackLocale) : string
    {
        if (in_array($locale, $this->getTranslatedLocales($key))) {
            return $locale;
        }

        if (! $useFallbackLocale) {
            return $locale;
        }

        if (! is_null($fallbackLocale = Config::get('translatable.fallback_locale'))) {
            return $fallbackLocale;
        }

        if (! is_null($fallbackLocale = Config::get('app.fallback_locale'))) {
            return $fallbackLocale;
        }

        return $locale;
    }

    protected function getLocale() : string
    {
        return Config::get('app.locale');
    }

    public function getTranslatableAttributes() : array
    {
        return is_array($this->translatable)
            ? $this->translatable
            : [];
    }

    public function getCasts() : array
    {
        return array_merge(
            parent::getCasts(),
            array_fill_keys($this->getTranslatableAttributes(), 'array')
        );
    }
}
