<?php

namespace Translation\Template;

use View;
use Config;

/**
 * Methods to assit in the creation of the localize sidebar UI
 * and the locale form field
 *
 * @todo Translation
 */
class Localize
{
    /**
     * The model instance being localized
     */
    protected $item;

    /**
     * The model class being localized
     *
     * @var string
     */
    protected $model;

    /**
     * Other localizations for the `$item`
     */
    protected $other_localizations;

    /**
     * The title of this model, from the controller
     *
     * @var string
     */
    protected $title;

    /**
     * Store a model instance
     *
     * @param  Illuminate\Database\Eloquent\Model $item
     * @return $this
     */
    public function item($item)
    {
        if (!$this->model) {
            $this->model = get_class($item);
        }

        $this->item = $item;

        return $this;
    }

    /**
     * Store the model class name
     *
     * @param  string $model
     * @return $this
     */
    public function model($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * The title of this model, from the controller
     *
     * @param  string $title
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Check if the localize UI should be displayed
     *
     * @return boolean
     */
    public function hidden()
    {
        $class = $this->model; // Must be a local var to test

        // There aren't multiple locales specified
        if (count(\Illuminate\Support\Facades\Config::get('site.site.locales')) <= 1 ) { return true;
        }

        // We're editing a model with no locale attribute
        if ($this->item && !$this->item->locale) { return true;
        }

        // The model was explicitly disabled
        if (!property_exists($class, 'localizable') || $class::$localizable === false ) {
            return true;
        }

        // Auto localize is turned on and we're on a child model
        if (\Illuminate\Support\Facades\Config::get('painel.core.auto_localize_root_models')
            && app('facilitador.wildcard')->detectParent()
        ) { return true;
        }

        // If auto-localizeable is turned off and this model doesn't have it
        // turned on
        if (!\Illuminate\Support\Facades\Config::get('painel.core.auto_localize_root_models')
            && property_exists($class, 'localizable') && !$class::$localizable
        ) { return true;
        }

        // Otherwise, allow localization
        return false;
    }

    /**
     * Get a hash of locales that are available for the item
     *
     * @return array
     */
    public function localizableLocales()
    {
        // Keep only locales that don't exist in ...
        return array_diff_key(
            Config::get('site.site.locales'),
            // ... the locales of other localizations ...
            $this->other()->pluck('locale')->flip()->toArray(),
            // ... and the model's locale
            [$this->item->locale => null]
        );
    }

    /**
     * Get other localizations, storing them internally for use in multiple places
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function other()
    {
        if ($this->other_localizations === null) {
            $this->other_localizations = $this->item->otherLocalizations()->get();
        }

        return $this->other_localizations;
    }

    /**
     * Render the sidebar, "Localize" UI
     *
     * @return string
     */
    public function __toString()
    {
        return View::make(
            'support::shared.form.relationships._localize', [
            'model' => $this->model,
            'item' => $this->item,
            'title' => $this->title,
            'localize' => $this,
            ]
        )->render();
    }
}
