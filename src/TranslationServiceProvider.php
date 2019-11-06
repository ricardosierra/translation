<?php
namespace RicardoSierra\Translation;

use Illuminate\Translation\FileLoader as LaravelFileLoader;
use Illuminate\Translation\TranslationServiceProvider as LaravelTranslationServiceProvider;
use RicardoSierra\Translation\Cache\RepositoryFactory as CacheRepositoryFactory;
use RicardoSierra\Translation\Commands\CacheFlushCommand;
use RicardoSierra\Translation\Commands\FileLoaderCommand;
use RicardoSierra\Translation\Loaders\CacheLoader;
use RicardoSierra\Translation\Loaders\DatabaseLoader;
use RicardoSierra\Translation\Loaders\FileLoader;
use RicardoSierra\Translation\Loaders\MixedLoader;
use RicardoSierra\Translation\Middleware\TranslationMiddleware;
use RicardoSierra\Translation\Models\Translation as TranslationModel;
use RicardoSierra\Translation\Repositories\LanguageRepository;
use RicardoSierra\Translation\Repositories\TranslationRepository;
use RicardoSierra\Translation\Routes\ResourceRegistrar;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use RicardoSierra\Translation\Contracts\Translation as TranslationInterface;

class TranslationServiceProvider extends LaravelTranslationServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/translator.php' => config_path('translator.php'),
            __DIR__ . '/../config/translation.php' => config_path('translation.php'),
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations/');


        $this->registerCacheRepository();
        $this->registerFileLoader();
        $this->registerCacheFlusher();
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

        parent::register();

        // Bind translation to the IoC.
        $this->app->bind('translation', function (Application $app) {
            return new Translation($app);
        });

        // Bind translation contract to IoC.
        $this->app->bind(TranslationInterface::class, 'translation');

        /**
         * Provider Antigo
         */
        Blade::directive('t', function ($args) {
            return "<?php echo App::make('translation')->translate{$args}; ?>";
        });

        $this->app->singleton('translation.uri.localizer', UriLocalizer::class);
        $this->app[\Illuminate\Routing\Router::class]->aliasMiddleware('localize', TranslationMiddleware::class);
        // Fix issue with laravel prepending the locale to localize resource routes:
        $this->app->bind('Illuminate\Routing\ResourceRegistrar', ResourceRegistrar::class);

        // Bind guzzle contract to IoC.
        $this->app->bind(ClientInterface::class, Client::class);

        // Include the helpers file for global `_t()` function
        include __DIR__.'/helpers.php';
    }

    /**
     *  IOC alias provided by this Service Provider.
     *
     *  @return array
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
        $this->app->singleton('translation.loader', function ($app) {
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
        });
    }

    /**
     *  Register the translation cache repository
     *
     *  @return void
     */
    public function registerCacheRepository()
    {
        $this->app->singleton('translation.cache.repository', function ($app) {
            $cacheStore = $app['cache']->getStore();
            return CacheRepositoryFactory::make($cacheStore, $app['config']->get('translator.cache.suffix'));
        });
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
     *  @return void
     */
    public function registerCacheFlusher()
    {
        //$cacheStore      = $this->app['cache']->getStore();
        //$cacheRepository = CacheRepositoryFactory::make($cacheStore, $this->app['config']->get('translator.cache.suffix'));
        $command = new CacheFlushCommand($this->app['translation.cache.repository'], $this->app['config']->get('translator.cache.enabled'));

        $this->app['command.translator:flush'] = $command;
        $this->commands('command.translator:flush');
    }
}
