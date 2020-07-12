<?php

namespace Translation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Translation\Facades\Translation;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class LocaleMiddleware
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * The response factory implementation.
     *
     * @var ResponseFactory
     */
    protected $response;

    /**
     * Create a new filter instance.
     *
     * @param  Guard           $auth
     * @param  ResponseFactory $response
     * @return void
     */
    public function __construct(Guard $auth,
        ResponseFactory $response
    ) {
        $this->auth = $auth;
        $this->response = $response;
    }

    /**
     * Sets the locale cookie on every request depending
     * on the locale supplied in the route prefix.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->auth->check()) {
            $language = (int) $this->auth->user()->language;
        }

        if (Cookie::has('locale')) {
            Config::set('app.locale', Cookie::get('locale'));
            app()->setLocale(Cookie::get('locale'));
        }

        if ($request->session()->has('locale')) {
            Config::set('app.locale', $request->session()->get('locale'));
            app()->setLocale($request->session()->get('locale'));
        }
        $request->cookies->set('locale', Translation::detectLocale($request));

        return $next($request);
    }
}
