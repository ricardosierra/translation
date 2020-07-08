<?php

namespace Translation\Observers;

use Translation\Models\Element;
use Config;
use Illuminate\Support\Str;

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
        
        if (!empty($model->locale)
            && empty($model->locale_group)
            && !is_a($model, Element::class) // Elements don't have groups
            && ($locales = Config::get('sitec.site.locales'))
            && count($locales) > 1
        ) {
            $model->setAttribute('locale_group', Str::random());
        }
    }
}
