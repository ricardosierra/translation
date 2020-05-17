<?php

namespace RicardoSierra\Translation\Loaders;

use RicardoSierra\Translation\Repositories\TranslationRepository;

class DatabaseLoader extends Loader
{
    /**
     *  The default locale.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     *  Translations repository.
     *
     * @var \RicardoSierra\Translation\Repositories\TranslationRepository
     */
    protected $translationRepository;

    /**
     *  Create a new mixed loader instance.
     *
     * @param string                                                        $defaultLocale
     * @param \RicardoSierra\Translation\Repositories\TranslationRepository $translationRepository
     */
    public function __construct($defaultLocale, TranslationRepository $translationRepository)
    {
        parent::__construct($defaultLocale);
        $this->translationRepository = $translationRepository;
    }

    /**
     *  Load the messages strictly for the given locale.
     *
     * @param  string $locale
     * @param  string $group
     * @param  string $namespace
     * @return array
     */
    public function loadSource($locale, $group, $namespace = '*')
    {
        $dotArray = $this->translationRepository->loadSource($locale, $namespace, $group);
        $undot    = [];
        foreach ($dotArray as $item => $text) {
            array_set($undot, $item, $text);
        }
        return $undot;
    }

    /**
     *  Add a new namespace to the loader.
     *
     * @param  string $namespace
     * @param  string $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
    }

    /**
     * Add a new JSON path to the loader.
     *
     * @param  string $path
     * @return void
     */
    public function addJsonPath($path)
    {
        //
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces()
    {
        return $this->hints;
    }
}
