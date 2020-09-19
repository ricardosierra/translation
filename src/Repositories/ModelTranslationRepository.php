<?php

namespace Translation\Repositories;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Application;
use Illuminate\Support\NamespacedItemResolver;
use Translation\Models\ModelTranslation;
use Carbon\Carbon;
use Translation;

class ModelTranslationRepository
{
    /**
     * Peguei do Siravel
     */

    public $model;

    public function __construct(ModelTranslation $translation)
    {
        $this->model = $translation;
    }

    public function getTranslation($code, $type, $lang)
    {
        list($language, $country) = [
            Translation::getLanguageCode($lang),
            Translation::getCountryCode($lang)
        ];
        if ($trans = ModelTranslation::where('entity_id', $code)->where('entity_type', $type)->where('language_code', $language)->where('country_code', $country)
            // ->where('entity_data', 'LIKE', '%"lang":"'.$lang.'"%')
            ->first()
        ) {
            return $trans;
        }

        return ModelTranslation::where('entity_id', $code)
            ->where('entity_type', $type)
            ->where('language_code', $language)
            ->first();
    }

    /**
     * Create or Update an entry
     *
     * @param integer $entityId
     * @param string  $entityType
     * @param string  $lang
     * @param array   $payload
     *
     * @return boolean
     */
    public function createOrUpdate($entityId, $entityType, $lang, $payload)
    {
        list($language, $country) = [
            Translation::getLanguageCode($lang),
            Translation::getCountryCode($lang)
        ];
        $translation = $this->model->firstOrCreate(
            [
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'language_code' => $language,
            'country_code' => $country,
            ]
        );

        unset($payload['_method']);
        unset($payload['_token']);

        $translation->entity_data = json_encode($payload);

        return $translation->save();
    }

    /**
     * Find by URL
     *
     * @param string $url
     * @param string $type
     *
     * @return Object|null
     */
    public function findByUrl($url, $type)
    {
        $item = $this->model->where('entity_type', $type)->where('entity_data', 'LIKE', '%"url":"'.$url.'"%')->first();

        if ($item && ($item->data->is_published == 1 || $item->data->is_published == 'on') && $item->data->published_at <= Carbon::now(\Illuminate\Support\Facades\Config::get('app.timezone'))->format('Y-m-d H:i:s')) {
            return $item->data;
        }

        return null;
    }

    /**
     * Find an entity by its Id
     *
     * @param integer $entityId
     * @param string  $entityType
     *
     * @return Object|null
     */
    public function findByEntityId($entityId, $entityType)
    {
        $item = $this->model->where('entity_type', $entityType)->where('entity_id', $entityId)->first();

        if ($item && ($item->data->is_published == 1 || $item->data->is_published == 'on') && $item->data->published_at <= Carbon::now(\Illuminate\Support\Facades\Config::get('app.timezone'))->format('Y-m-d H:i:s')) {
            return $item->data;
        }

        return null;
    }

    /**
     * Get entities by type and language
     *
     * @param string $lang
     * @param string $type
     *
     * @return Illuminate\Support\Collection
     */
    public function getEntitiesByTypeAndLang($lang, $type)
    {
        $entities = collect();
        $collection = $this->model->where('entity_type', $type)->where('entity_data', 'LIKE', '%"lang":"'.$lang.'"%')->get();

        foreach ($collection as $item) {
            $instance = app($item->type)->attributes = $item->data;
            $entities->push($instance);
        }

        return $entities;
    }
}
