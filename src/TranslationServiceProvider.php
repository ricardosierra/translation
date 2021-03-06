<?php
namespace Translation;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader as LaravelFileLoader;
use Illuminate\Translation\TranslationServiceProvider as LaravelTranslationServiceProvider;
use Translation\Cache\RepositoryFactory as CacheRepositoryFactory;
use Translation\Commands\CacheFlushCommand;
use Translation\Commands\FileLoaderCommand;

use Muleta\Traits\Providers\ConsoleTools;
use Translation\Contracts\Translation as TranslationInterface;
use Translation\Http\Middleware\LocaleMiddleware;
use Translation\Loaders\CacheLoader;
use Translation\Loaders\DatabaseLoader;
use Translation\Loaders\FileLoader;
use Translation\Loaders\MixedLoader;
use Translation\Middleware\TranslationMiddleware;
use Translation\Models\Translation as TranslationModel;
use Translation\Repositories\LanguageRepository;

use Translation\Repositories\TranslationRepository;
use Translation\Routes\ResourceRegistrar;
use Translation\Translator\Collection as TranslatorCollection;

class TranslationServiceProvider extends LaravelTranslationServiceProvider
{
    
    use ConsoleTools;

    public $packageName = 'translation';
    const pathVendor = 'ricardosierra/translation';

    public static $aliasProviders = [
        // 'Translation' => \Translation\Facades\Translation::class,
    ];

    public static $providers = [

        // \Translation\TranslationServiceProvider::class,

        
    ];
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public static $menuItens = [
        
    ];
    
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        Schema::defaultStringLength(191);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/');
        $this->publishes(
            [
            __DIR__ . '/../config/translator.php' => config_path('translator.php'),
            __DIR__ . '/../config/translation.php' => config_path('translation.php'),
            ]
        );

        $router->pushMiddlewareToGroup('web', LocaleMiddleware::class);

        $this->routes();


        $this->registerFileLoader();
        $this->registerCacheFlusher();
        /**
         * Provider Antigo
         */
        Blade::directive(
            't',
            function ($args) {
                return "<?php echo App::make('translation')->translate{$args}; ?>";
            }
        );
        Blade::directive(
            'lang',
            function ($args) {
                return "<?php echo App::make('translation')->translate{$args}; ?>";
            }
        );


        $this->bootTranslatorCollectionMacros();
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        /**
         * Transmissor; Routes
         */
        $this->loadRoutesForRiCa(__DIR__.'/../routes');
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translator.php', 'translator');
        $this->mergeConfigFrom(__DIR__ . '/../config/translation.php', 'translation');


        $this->registerCacheRepository();

        parent::register();

        // Bind translation to the IoC.
        $this->app->bind(
            'translation',
            function (Application $app) {
                return new \Translation\Services\Translation($app);
            }
        );

        // Bind translation contract to IoC.
        $this->app->bind(TranslationInterface::class, 'translation');

        $this->app->singleton('translation.uri.localizer', \Translation\Services\UriLocalizer::class);
        $this->app[\Illuminate\Routing\Router::class]->aliasMiddleware('localize', TranslationMiddleware::class);
        // Fix issue with laravel prepending the locale to localize resource routes:
        $this->app->bind('Illuminate\Routing\ResourceRegistrar', ResourceRegistrar::class);

        // Bind guzzle contract to IoC.
        $this->app->bind(ClientInterface::class, Client::class);

        // Include the helpers file for global `_t()` function
        include __DIR__.'/helpers.php';



        $this->app['events']->listen(
            'eloquent.saving:*',
            '\Translation\Observers\Localize'
        );


        /*
        |--------------------------------------------------------------------------
        | Register the Commands
        |--------------------------------------------------------------------------
        */
        // Register commands
        $this->registerCommandFolders(
            [
            base_path('vendor/ricardosierra/translation/src/Console/Commands') => '\Translation\Console\Commands',
            ]
        );
    }

    /**
     *  IOC alias provided by this Service Provider.
     *
     * @return array
     */
    public function provides()
    {
        $this->app->register(\Illuminate\Validation\ValidationServiceProvider::class);

        return array_merge(parent::provides(), ['translation.cache.repository', 'translation.uri.localizer', 'translation.loader', 'translation']);
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $app = $this->app;
        $this->app->singleton(
            'translation.loader',
            function ($app) {
                $defaultLocale = $app['config']->get('app.locale');
                $loader        = null;
                $source        = $app['config']->get('translator.source');

                switch ($source) {
                case 'mixed':
                    $laravelFileLoader = new LaravelFileLoader($app['files'], $app->basePath() . '/resources/lang');
                    $fileLoader        = new FileLoader($defaultLocale, $laravelFileLoader);
                    $databaseLoader    = new DatabaseLoader($defaultLocale, $app->make(TranslationRepository::class));
                    $loader            = new MixedLoader($defaultLocale, $fileLoader, $databaseLoader);
break;
                case 'mixed_db':
                    $laravelFileLoader = new LaravelFileLoader($app['files'], $app->basePath() . '/resources/lang');
                    $fileLoader        = new FileLoader($defaultLocale, $laravelFileLoader);
                    $databaseLoader    = new DatabaseLoader($defaultLocale, $app->make(TranslationRepository::class));
                    $loader            = new MixedLoader($defaultLocale, $databaseLoader, $fileLoader);
break;
                case 'database':
                    $loader = new DatabaseLoader($defaultLocale, $app->make(TranslationRepository::class));
break;
                default:case 'files':
                        $laravelFileLoader = new LaravelFileLoader($app['files'], $app->basePath() . '/resources/lang');
                        $loader            = new FileLoader($defaultLocale, $laravelFileLoader);
break;
                }
                if ($app['config']->get('translator.cache.enabled')) {
                    $loader = new CacheLoader($defaultLocale, $app['translation.cache.repository'], $loader, $app['config']->get('translator.cache.timeout'));
                }
                return $loader;
            }
        );
    }

    /**
     *  Register the translation cache repository
     *
     * @return void
     */
    public function registerCacheRepository()
    {
        $this->app->singleton(
            'translation.cache.repository',
            function ($app) {
                $cacheStore = $app['cache']->getStore();
                return CacheRepositoryFactory::make($cacheStore, $app['config']->get('translator.cache.suffix'));
            }
        );
    }

    /**
     * Register the translator:load language file loader.
     *
     * @return void
     */
    protected function registerFileLoader()
    {
        $app                   = $this->app;
        $defaultLocale         = $app['config']->get('app.locale');
        $languageRepository    = $app->make(LanguageRepository::class);
        $translationRepository = $app->make(TranslationRepository::class);
        $translationsPath      = $app->basePath() . '/resources/lang';
        $command               = new FileLoaderCommand($languageRepository, $translationRepository, $app['files'], $translationsPath, $defaultLocale);

        $this->app['command.translator:load'] = $command;
        $this->commands('command.translator:load');
    }

    /**
     *  Flushes the translation cache
     *
     * @return void
     */
    public function registerCacheFlusher()
    {
        //$cacheStore      = $this->app['cache']->getStore();
        //$cacheRepository = CacheRepositoryFactory::make($cacheStore, $this->app['config']->get('translator.cache.suffix'));
        $command = new CacheFlushCommand($this->app['translation.cache.repository'], $this->app['config']->get('translator.cache.enabled'));

        $this->app['command.translator:flush'] = $command;
        $this->commands('command.translator:flush');
    }

    protected function bootTranslatorCollectionMacros()
    {
        Collection::macro(
            'translate',
            function () {
                $transtors = [];

                foreach ($this->all() as $item) {
                    $transtors[] = call_user_func_array([$item, 'translate'], func_get_args());
                }

                return new TranslatorCollection($transtors);
            }
        );
    }
}
