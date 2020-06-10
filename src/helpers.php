<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

if (Config::get('translation.shorthand_enabled')) {
    if (!function_exists('_t')) {
        /**
         * Shorthand function for translating text.
         *
         * @param string $text
         * @param array  $replacements
         * @param string $toLocale
         *
         * @return string
         */
        function _t($text, $replacements = [], $toLocale = '')
        {
            return App::make('translation')->translate($text, $replacements, $toLocale);
        }
    }
}

if (!function_exists('is_field_translatable')) {
    /**
     * Check if a Field is translatable.
     *
     * @param Illuminate\Database\Eloquent\Model      $model
     * @param Illuminate\Database\Eloquent\Collection $row
     */
    function is_field_translatable($model, $row)
    {
        if (!is_bread_translatable($model)) {
            return;
        }

        return $model->translatable()
            && method_exists($model, 'getTranslatableAttributes')
            && in_array($row->field, $model->getTranslatableAttributes());
    }
}

if (!function_exists('get_field_translations')) {
    /**
     * Return all field translations.
     *
     * @param Illuminate\Database\Eloquent\Model $model
     * @param string                             $field
     * @param string                             $rowType
     * @param bool                               $stripHtmlTags
     */
    function get_field_translations($model, $field, $rowType = '', $stripHtmlTags = false)
    {
        $_out = $model->getTranslationsOf($field);

        if ($stripHtmlTags && $rowType == 'rich_text_box') {
            foreach ($_out as $language => $value) {
                $_out[$language] = strip_tags($_out[$language]);
            }
        }

        return json_encode($_out);
    }
}

if (!function_exists('is_bread_translatable')) {
    /**
     * Check if BREAD is translatable.
     *
     * @param Illuminate\Database\Eloquent\Model $model
     */
    function is_bread_translatable($model)
    {
        return \Illuminate\Support\Facades\Config::get('site.multilingual.enabled', true)
            && isset($model)
            && method_exists($model, 'translatable')
            && $model->translatable();
    }
}

if (!function_exists('__')) {
    function __($key, array $par = [])
    {
        return trans($key, $par);
    }
}
