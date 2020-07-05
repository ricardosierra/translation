<?php

namespace RicardoSierra\Translation\Repositories;

use RicardoSierra\Translation\Models\Language;
use RicardoSierra\Translation\Models\Locale;
use RicardoSierra\Translation\Models\Country;


class LangRepository
{
    /**
     * @var string
     */
    const DEFAULT_LOCALE = 'pt-BR';

    /**
     * @var string
     */
    const DEFAULT_COUNTRY = 'Brasil';

    /**
     * @var string
     */
    const COOKIENAME = 'language';

    /**
     * @return string
     */
    public static function getDefaultLocale()
    {
        return self::DEFAULT_LOCALE;
    }

    /**
     * @return array
     */
    public static function getLocale()
    {
        return [
            'en-GB',
            'fr-FR',
            'es-CO',
            'pt-BR',
        ];
    }

    /**
     * @param  mixed  $locale   (optional)
     * @param  string $column   (optional)
     * @param  string $inLocale (optional)
     * @return array
     */
    public static function get($locale = null, $column = null, $inLocale = self::DEFAULT_LOCALE)
    {
        $allLocale = self::getLocale();

        if ($locale) {
            $locale = (array) $locale;
            foreach ($locale as $value) {
                $bestLocale[] = \Locale::lookup($allLocale, $value, false, self::getDefaultLocale());
            }
            $allLocale = $bestLocale;
        }

        return self::configure($allLocale, $column, $inLocale);
    }

    /**
     * @param array  $locale
     * @param string $column
     * @param string $inLocale
     */
    protected static function configure($locale, $column, $inLocale)
    {
        foreach ($locale as $key) {
            $configured[$key] = [
                'locale' => $key,
                'name' => utf8_decode(\Locale::getDisplayName($key, $inLocale)),
                'region' => utf8_decode(\Locale::getDisplayRegion($key, $inLocale)),
                'language' => utf8_decode(\Locale::getDisplayLanguage($key, $inLocale)),
                'class' => 'flag-icon flag-icon-' . strtolower(\Locale::getRegion($key))
            ];
        }

        if ($column) {
            return array_column($configured, $column, 'locale');
        }

        return $configured;
    }

    /**
     * @return array
     */
    public static function getCurrent()
    {
        //        if ($cookieLocale = self::getCookie()) {
        //            return current(self::get($cookieLocale));
        //        }
        //
        //        if (!empty(Yii::app()->user->model()->language)) {
        //            $userLocale = Yii::app()->user->model()->language;
        //            return current(self::get($userLocale));
        //        }


        if (session('language')!==null) {
            //Config::set('app.locale', session('language'));
            return current(self::get(session('language')));
        }

        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLocale = \Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            return current(self::get($browserLocale));
        }

        return current(self::all(self::getDefaultLocale()));
    }

    /**
     * @param  string $language
     * @return bool
     */
    public static function updateCookie($language)
    {
        return (!self::getCookie()) ?: self::setCookie($language);
    }

    /**
     * @return bool
     */
    public static function getCookie()
    {
        if (empty($_COOKIE[self::COOKIENAME])) {
            return false;
        }

        return $_COOKIE[self::COOKIENAME];
    }

}
