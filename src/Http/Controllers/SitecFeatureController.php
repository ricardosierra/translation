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
     * @param string  $language
     */
    public function setLanguage(Request $request, $language)
    {
        dd($language);
        Translation::setLanguage($language);
        return back()->withCookie('language', $language)->withCookie('locale', $language);
    }
}
