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
}
