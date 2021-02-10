<?php

namespace Translation\Repositories;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Application;
use Illuminate\Support\NamespacedItemResolver;
use Translation\Models\Translation as Translation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Filesystem\Filesystem;
use File;
use Illuminate\Support\Str;
use Translation\Traits\ManipuleFileLangTrait;

class LangResourcesRepository extends Repository
{
    use ManipuleFileLangTrait;
    // /**
    //  * @var \Illuminate\Database\Connection
    //  */
    // protected $database;

    // /**
    //  * The model being queried.
    //  *
    //  * @var \Translation\Models\Translation
    //  */
    // protected $model;

    // /**
    //  *  Validator
    //  *
    //  * @var \Illuminate\Validation\Validator
    //  */
    // protected $validator;

    // /**
    //  *  Validation errors.
    //  *
    //  * @var \Illuminate\Support\MessageBag
    //  */
    // protected $errors;
    protected $languages = [];

    protected $allOptionsTranslation = [];

    // /**
    //  *  Constructor
    //  *
    //  * @param  \Translation\Models\Translation  $model     Bade model for queries.
    //  * @param  \Illuminate\Validation\Validator $validator Validator factory
    //  * @return void
    //  */
    // public function __construct(Translation $model, Application $app)
    // {
    //     $this->model         = $model;
    //     $this->app           = $app;
    //     $this->defaultLocale = $app['config']->get('app.locale');
    //     $this->database      = $app['db'];
    // }
    /**
     *  Constructor
     *
     * @param  \Translation\Models\Translation  $model     Bade model for queries.
     * @param  \Illuminate\Validation\Validator $validator Validator factory
     * @return void
     */
    public function __construct()
    {
        $inputPath = base_path('resources/lang');
        $languagesOptions = File::directories($inputPath);
        foreach ($languagesOptions as $langDir)
        {
            $this->addLanguageFolder($langDir);
        }
        
    }
    public function addLanguageFolder($langDir)
    {
        $lang = Str::afterLast($langDir, '/');
        $this->languages[$lang] = [
            'code' => $lang,
            'langDir' => $langDir,
            'options' => [

            ]
        ];
        $allFiles = File::files($langDir);
        // $allFiles = File::allFiles($langDir); //@todo pegar subPastas
        foreach ($allFiles as $f)
        {
            $f = $f->getPathname();
            if (!Str::endsWith($f, '.php')) {
                //@todo processar .yml 
                \Log::info('Não processando sem ser php -> '. $f);
                continue;
            }
            $indice = Str::between($f, '/', '.php');
            $indice = Str::afterLast($indice, '/');
            $this->languages[$lang]['options'][$indice] = $this->processFileLang($f, $indice);
        }

    }
    private function processFileLang($f, $indice)
    {
        $data = include($f);
        if (!is_array($data)) dd($data, $f, $indice); //@todo
        if (!isset($this->allOptionsTranslation[$indice])) {
            $this->allOptionsTranslation[$indice] = [];
        }
        foreach ($data as $option => $result) {
            if (!in_array($option, $this->allOptionsTranslation[$indice])) {
                $this->allOptionsTranslation[$indice][] = $option;
            }
        }
        return $data;
    }




    public function getMissingForLang($lang)
    {
        if (!isset($this->languages[$lang])) {
            $allLangs = [];
            foreach($this->languages as $value) {
                $allLangs = $value['code'];
            }
            dd($allLangs, $lang);
        }

        $langData = $this->languages[$lang];

        $missing = [];

        foreach($this->allOptionsTranslation as $file=>$fileIndices) {

            if (!isset($langData['options'][$file])) {
                $missing[$file] = $file;
            } else {
                $missing[$file] = [];
                foreach($fileIndices as $fileIndice) {
                    if (!isset($langData['options'][$file][$fileIndice])) {
                        $missing[$file][$fileIndice] = $fileIndice;
                    }
                }
            }
        }

        return $missing;
    }

    public function mergeLangs($langTo, $langFrom)
    {

        $langDataTo = $this->languages[$langTo];
        $langDataFrom = $this->languages[$langFrom];

        $missing = [];
        foreach($langDataFrom['options'] as $file=>$fileIndices) {
            $update = false;
            $fileName = $langDataTo['langDir'] . DIRECTORY_SEPARATOR . $file ;
            if (!isset($langDataTo['options'][$file])) {
                File::move($fileName. '.php', $langDataTo['langDir'] . DIRECTORY_SEPARATOR . $file . '.php');
                $langDataTo['options'][$file] = $langDataFrom['options'][$file];
            } else {
                foreach($fileIndices as $fileIndice=>$indiceValue) {
                    if (!isset($langDataTo['options'][$file][$fileIndice])) {
                        $update = true;
                        $langDataTo['options'][$file][$fileIndice] = $indiceValue;
                    }
                }
            }

            if (!$update) {
                // @todo escrever arquivo
                $formatted = $this->formatFileContents($langTo, $file);
                $this->writeLangFile($langTo, $file, $formatted);
                $update = false;
            }
        }
    }
}
