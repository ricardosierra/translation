<?php

namespace RicardoSierra\Translation\Services\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Repositories\LangRepository;

trait LangServiceTrait
{

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