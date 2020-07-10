<?php

namespace Translation\Http\Controllers;

use Translation\Services\LanguageService;
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
        LanguageService::setLanguage($lang);
        return back()->withCookie('language', $lang);
    }
}
