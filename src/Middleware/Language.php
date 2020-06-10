<?php

namespace RicardoSierra\Translation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class Language
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
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->auth->check()) {
            $language = (int) $this->auth->user()->language;
        }

        if (Cookie::has('language')) {
            Config::set('app.locale', Cookie::get('language'));
            app()->setLocale(Cookie::get('language'));
        }

        if ($request->session()->has('language')) {
            Config::set('app.locale', $request->session()->get('language'));
            app()->setLocale($request->session()->get('language'));
        }

        return $next($request);
    }
}
