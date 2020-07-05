<?php

namespace RicardoSierra\Translation\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Boravel\Models\System\Language;
use Illuminate\Support\Facades\Auth;

class LanguageService extends Service
{

    public function getActualLanguage()
    {
        return $this->getActualLanguageCode();
    }

    public function getMenuLanguage()
    {
        $actual = $this->getActualLanguage();
        $languages = $this->getAllLanguages();
        if (empty($languages) || count($languages)<=1) {
            return '';
        }

        $activeHtml = config('app.locale');

        $html = '<div class="dropdown-menu dropdown-menu-right" aria-labelledby="languages">';
            foreach ($languages as $language) {
                $active = '';
                if ($language->code == $actual) {
                    $active = ' active';
                    $activeHtml = $language->code;
                }
                $html .= '<a class="dropdown-item'.$active.'" href="'.route('language.set', ['language' => $language->code]).'">'.$language->name.'</a>';
            }
        $html .= '</div>';

        return '<li class="nav-item dropdown">'.
            '<a class="nav-item nav-link dropdown-toggle mr-md-2" href="#" id="languages" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.
                $activeHtml.
            '</a>'.$html.'</li>';
    }

    public function setLanguage($code)
    {
        CacheService::get('language', $code);
        if ($user = Auth::user()) {
            $user->language_code = $code;
            $user->save();
        }

        return true;
    }

    private function getActualLanguageCode()
    {
        $cacheLanguage = CacheService::get('language');
        if (!empty($cacheLanguage)) {
            return $cacheLanguage;
        }

        return config('app.locale');
    }

    public function getAllLanguages()
    {
        return Language::all();
    }

    /**
     * From FAcilitador LangServiceTrait
     */


    /**
     * Return menu for translation
     *
     * @return string
     */
    public function menu_lang()
    {
        $langs = LangRepository::get();

        if (!$langs || empty($langs)) {
            return '';
        }

        $current = LangRepository::getCurrent();

        $response = '<li>';
        $response .= '<a href=""><span class="'.$current['class'].'"></span></a>';
        $response .= '<ul class="sub-menu clearfix">';
        $response .= '<li class=\'no-translation menu-item current-lang \'><a href="'.url('sitec/language/set/'.$current['locale']).'"><span class="'.$current['class'].'"></span></a></li>';

        foreach ($langs as $lang) {
            if ($lang['locale'] !== $current['locale']) {
                $response .= '<li class=\'no-translation menu-item\'><a href="'.url('sitec/language/set/'.$lang['locale']).'"><span class="'.$lang['class'].'"></span></a></li>';
            }
        }

        $response .= '</ul></li>';

        return $response;
    }

    /**
     * Get a especific image for current Lang.
     *
     * @param string $img_path
     *
     * @return string
     */
    public function img_lang($img_path)
    {
        $public_path = public_path();

        $current = LangRepository::getCurrent();
        $min_lang = explode('-', $current['locale']);

        $break_path = explode('.', $img_path);

        $extensao = array_pop($break_path);

        if (file_exists($public_path.'/'.implode('.', $break_path).'-'.$current['locale'].'.'.$extensao)) {
            return implode('.', $break_path).'-'.$current['locale'].'.'.$extensao;
        }

        if (file_exists($public_path.'/'.implode('.', $break_path).'-'.$min_lang[0].'.'.$extensao)) {
            return implode('.', $break_path).'-'.$min_lang[0].'.'.$extensao;
        }

        return $img_path;
    }
}
