<?php

namespace Translation\Http\Controllers;

// use Translation\Services\LanguageService;
use Translation;
use Illuminate\Http\Request;

class SitecFeatureController extends Controller
{
    /**
     * Set the default lanugage for the session.
     *
     * @param Request $request
     * @param string  $lang
     */
    public function setLanguage(Request $request, $lang)
    {
        Translation::setLanguage($lang);
        return back()->withCookie('language', $lang)->withCookie('locale', $lang);
    }
}
