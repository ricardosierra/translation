<?php

namespace Translation\Observers;

use Translation\Models\Element;
use Translation\Models\Translation as TranslationModel;
use Config;
use Illuminate\Support\Str;
use Event;
use Log;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;
use Illuminate\Support\Facades\Schema;
use Auth;
use Translation;
use Translation\Services\GoogleTranslate;

/**
 * Generate a locale_group attribute for localized models if
 * one doesn't already exist.
 */
class Localize
{
    /**
     * Called on model saving
     *
     * @param  string $event
     * @param  array  $payload Contains:
     *                         -
     *                         Translation\Models\Base
     *                         $model
     * @return void
     */
    public function handle($event, $payload)
    {
        list($model) = $payload;

        // dd(app()->bound(Translation::class));
        // // Ignore
        // if (!app()->bound(Translation::class)) {
        //     return true;
        // }

        // Get the action from the event name
        preg_match('#\.(\w+)#', $event, $matches);
        $action = $matches[1];

        // If there is matching callback method on the model, call it, passing
        // any additional event arguments to it
        $method = 'on'.Str::studly($action);
        
        if (method_exists($this, $method)) {
            return $this->$method($model);
        }
    }


    /**
     * 
     * @param object $payload
     */
    public function onSaving($model)
    {
        return $this->onCreating($model);
    }

    /**
     * After the item is created in the database.
     *
     * @param object $payload
     */
    public function onCreating($model)
    {
        if (Schema::hasColumn($model->getTable(), 'locale') && 
            !empty($model->locale)
                && empty($model->locale_group)
                && !is_a($model, Element::class) // Elements don't have groups
                && ($locales = Config::get('sitec.site.locales'))
                && count($locales) > 1
        ) {
            $model->setAttribute('locale_group', Str::random());
        }

        if (Schema::hasColumn($model->getTable(), 'language_code') && empty($model->language_code)){
            $model->language_code = Translation::getLanguageCode();
        }

        if (Schema::hasColumn($model->getTable(), 'country_code') && empty($model->country_code)){
            $model->language_code = Translation::getCountryCode();
        }

        return true;
        
    }


    /**
     * After the item is created in the database.
     *
     * @param object $model
     */
    public function onCreated($model)
    // public function afterCreate($model)
    {
        if (!Schema::hasColumn($model->getTable(), 'language_code'))
        {
            return true;
        }

        if (!config('siravel.auto-translate', false)) {
            return true;
        }

        $entry = $model->toArray();

        unset($entry['created_at']);
        unset($entry['updated_at']);
        unset($entry['translations']);
        unset($entry['is_published']);
        unset($entry['published_at']);
        unset($entry['id']);

        foreach (config('siravel.languages') as $code => $language) {
            if ($code != config('siravel.default-language')) {
                $tr = new GoogleTranslate(config('siravel.default-language'), $code);
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
                app(ModelTranslationRepository::class)->createOrUpdate($entityId, $entityType, $code, $translation);
            }
        }
        return true;
    }

    /**
     * When the item is being deleted.
     *
     * @param object $payload
     */
    public function onDeleting($payload)
    {
        $type = get_class($payload);
        $id = $payload->id;

        TranslationModel::where('entity_id', $id)->where('entity_type', $type)->delete();
        return true;
    }
}
