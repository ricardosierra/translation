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
 */
trait ManipuleFileLangTrait
{


    /**
     * Format the contents of a single translation file in the given language.
     * @param string $lang
     * @param string $fileName
     * @return string
     */
    public function formatFileContents(string $lang, string $fileName) : string
    {
        $enLines = $this->loadLangFileLines('en', $fileName);
        $langContent = $this->loadLang($lang, $fileName);
        $enContent = $this->loadLang('en', $fileName);

        // Calculate the longest top-level key length
        $longestKeyLength = $this->calculateKeyPadding($enContent);

        // Start formatted content
        $formatted = [];
        $mode = 'header';
        $skipArray = false;
        $arrayKeys = [];

        foreach ($enLines as $index => $line) {
            $trimLine = trim($line);
            if ($mode === 'header') {
                $formatted[$index] = $line;
                if (str_replace(' ', '', $trimLine) === 'return[') {
                    $mode = 'body';
                }
            }

            if ($mode === 'body') {
                $matches = [];
                $arrayEndMatch = preg_match('/]\s*,\s*$/', $trimLine);

                if ($skipArray) {
                    if ($arrayEndMatch) {
                        $skipArray = false;
                    }
                    continue;
                }

                // Comment to ignore
                if (strpos($trimLine, '//!') === 0) {
                    $formatted[$index] = "";
                    continue;
                }

                // Comment
                if (strpos($trimLine, '//') === 0) {
                    $formatted[$index] = "\t" . $trimLine;
                    continue;
                }

                // Arrays
                $arrayStartMatch = preg_match('/^\'(.*)\'\s+?=>\s+?\[(\],)?\s*?$/', $trimLine, $matches);

                $indent = count($arrayKeys) + 1;
                if ($arrayStartMatch === 1) {
                    if ($fileName === 'settings' && $matches[1] === 'language_select') {
                        $skipArray = true;
                        continue;
                    }
                    $arrayKeys[] = $matches[1];
                    $formatted[$index] = str_repeat(" ", $indent * 4) . str_pad("'{$matches[1]}'", $longestKeyLength) . "=> [";
                    if ($arrayEndMatch !== 1) {
                        continue;
                    }
                }
                if ($arrayEndMatch === 1) {
                    $this->unsetArrayByKeys($langContent, $arrayKeys);
                    array_pop($arrayKeys);
                    if (isset($formatted[$index])) {
                        $formatted[$index] .= '],';
                    } else {
                        $formatted[$index] = str_repeat(" ", ($indent-1) * 4) . "],";
                    }
                    continue;
                }

                // Translation
                $translationMatch = preg_match('/^\'(.*)\'\s+?=>\s+?[\'"](.*)?[\'"].+?$/', $trimLine, $matches);
                if ($translationMatch === 1) {
                    $key = $matches[1];
                    $keys = array_merge($arrayKeys, [$key]);
                    $langVal = $this->getTranslationByKeys($langContent, $keys);
                    if (empty($langVal)) {
                        continue;
                    }

                    $keyPad = $longestKeyLength;
                    if (count($arrayKeys) === 0) {
                        unset($langContent[$key]);
                    } else {
                        $keyPad = $this->calculateKeyPadding($this->getTranslationByKeys($enContent, $arrayKeys));
                    }

                    $formatted[$index] = $this->formatTranslationLine($key, $langVal, $indent, $keyPad);
                    continue;
                }
            }
        }

        // Fill missing lines
        $arraySize = max(array_keys($formatted));
        $formatted = array_replace(array_fill(0, $arraySize, ''), $formatted);

        // Add remaining translations
        $langContent = array_filter($langContent, function ($item) {
            return !is_null($item) && !empty($item);
        });
        if (count($langContent) > 0) {
            $formatted[] = '';
            $formatted[] = "\t// Unmatched";
        }
        foreach ($langContent as $key => $value) {
            if (is_array($value)) {
                $formatted[] = $this->formatTranslationArray($key, $value);
            } else {
                $formatted[] = $this->formatTranslationLine($key, $value);
            }
        }

        // Add end line
        $formatted[] = '];';
        return implode("\n", $formatted);
    }

    /**
     * Format a translation line.
     * @param string $key
     * @param string $value
     * @param int $indent
     * @param int $keyPad
     * @return string
     */
    public function formatTranslationLine(string $key, string $value, int $indent = 1, int $keyPad = 1) : string
    {
        $start = str_repeat(" ", $indent * 4) . str_pad("'{$key}'", $keyPad, ' ');
        if (strpos($value, "\n") !== false) {
            $escapedValue = '"' .  str_replace("\n", '\n', $value)  . '"';
            $escapedValue = '"' .  str_replace('"', '\"', $escapedValue)  . '"';
        } else {
            $escapedValue = "'" . str_replace("'", "\\'", $value) . "'";
        }
        return "{$start} => {$escapedValue},";
    }

    /**
     * Find the longest key in the array and provide the length
     * for all keys to be used when printed.
     * @param array $array
     * @return int
     */
    public function calculateKeyPadding(array $array) : int
    {
        $top = 0;
        foreach ($array as $key => $value) {
            $keyLen = strlen($key);
            $top = max($top, $keyLen);
        }
        return min(35, $top + 2);
    }

    /**
     * Format an translation array with the given key.
     * Simply prints as an old-school php array.
     * Used as a last-resort backup to save unused translations.
     * @param string $key
     * @param array $array
     * @return string
     */
    public function formatTranslationArray(string $key, array $array) : string
    {
        $arrayPHP = var_export($array, true);
        return "    '{$key}' => {$arrayPHP},";
    }

    /**
     * Find a string translation value within a multi-dimensional array
     * by traversing the given array of keys.
     * @param array $translations
     * @param array $keys
     * @return string|array
     */
    public function getTranslationByKeys(array $translations, array $keys)
    {
        $val = $translations;
        foreach ($keys as $key) {
            $val = $val[$key] ?? '';
            if ($val === '') {
                return '';
            }
        }
        return $val;
    }

    /**
     * Unset an inner item of a multi-dimensional array by
     * traversing the given array of keys.
     * @param array $input
     * @param array $keys
     */
    public function unsetArrayByKeys(array &$input, array $keys)
    {
        $val = &$input;
        $lastIndex = count($keys) - 1;
        foreach ($keys as $index => &$key) {
            if ($index === $lastIndex && is_array($val)) {
                unset($val[$key]);
            }
            if (!is_array($val)) {
                return;
            }
            $val = &$val[$key] ?? [];
        }
    }

    /**
     * Write the given content to a translation file.
     * @param string $lang
     * @param string $fileName
     * @param string $content
     */
    public function writeLangFile(string $lang, string $fileName, string $content)
    {
        $path = 
        $inputPath = base_path('resources/lang') . "/{$lang}/{$fileName}.php";
        if (!file_exists($path)) {
            $this->errorOut("Expected translation file '{$path}' does not exist");
        }
        file_put_contents($path, $content);
    }

    /**
     * Load the contents of a language file as an array of text lines.
     * @param string $lang
     * @param string $fileName
     * @return array
     */
    public function loadLangFileLines(string $lang, string $fileName) : array
    {
        $path = 
        $inputPath = base_path('resources/lang') . "/{$lang}/{$fileName}.php";
        if (!file_exists($path)) {
            $this->errorOut("Expected translation file '{$path}' does not exist");
        }
        $lines = explode("\n", file_get_contents($path));
        return array_map(function ($line) {
            return trim($line, "\r");
        }, $lines);
    }

    /**
     * Load the contents of a language file
     * @param string $lang
     * @param string $fileName
     * @return array
     */
    public function loadLang(string $lang, string $fileName) : array
    {
        $path = 
        $inputPath = base_path('resources/lang') . "/{$lang}/{$fileName}.php";
        if (!file_exists($path)) {
            $this->errorOut("Expected translation file '{$path}' does not exist");
        }

        $fileData = include($path);
        return $fileData;
    }

    /**
     * Fetch an array containing the names of all translation files without the extension.
     * @return array
     */
    public function getTranslationFileNames() : array
    {
        $dir = 
        $inputPath = base_path('resources/lang') . "/en";
        if (!file_exists($dir)) {
            $this->errorOut("Expected directory '{$dir}' does not exist");
        }
        $files = scandir($dir);
        $fileNames = [];
        foreach ($files as $file) {
            if (substr($file, -4) === '.php') {
                $fileNames[] = substr($file, 0, strlen($file) - 4);
            }
        }
        return $fileNames;
    }

    /**
     * Format a locale to follow the lowercase_UPERCASE standard
     * @param string $lang
     * @return string
     */
    public function formatLocale(string $lang) : string
    {
        $langParts = explode('_', strtoupper($lang));
        $langParts[0] = strtolower($langParts[0]);
        return implode('_', $langParts);
    }

    /**
     * Dump a variable then die.
     * @param $content
     */
    public function dd($content)
    {
        print_r($content);
        exit(1);
    }

    /**
     * Log out some information text in blue
     * @param $text
     */
    public function info($text)
    {
        echo "\e[34m" . $text . "\e[0m\n";
    }

    /**
     * Log out an error in red and exit.
     * @param $text
     */
    public function errorOut($text)
    {
        echo "\e[31m" . $text . "\e[0m\n";
        exit(1);
    }
}