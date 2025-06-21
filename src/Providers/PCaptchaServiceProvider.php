<?php

namespace Core45\LaravelPCaptcha\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Core45\LaravelPCaptcha\Services\PCaptchaService;
use Core45\LaravelPCaptcha\Middleware\ProtectWithPCaptcha;
use Core45\LaravelPCaptcha\Console\InstallCommand;

class PCaptchaServiceProvider extends ServiceProvider
{
    /**
     * Register package services
     */
    public function register(): void
    {
        // Register the main service
        $this->app->singleton('p-captcha', function ($app) {
            return new PCaptchaService();
        });

        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/p-captcha.php', 'p-captcha'
        );
    }

    /**
     * Bootstrap package services
     */
    public function boot(): void
    {
        // Register middleware
        $this->registerMiddleware();

        // Register routes
        $this->registerRoutes();

        // Register views
        $this->registerViews();

        // Register Blade directive
        $this->registerBladeDirective();

        // Register commands
        $this->registerCommands();

        // Publish assets and config
        $this->registerPublishing();
    }

    /**
     * Register package middleware
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('p-captcha', ProtectWithPCaptcha::class);
    }

    /**
     * Register package routes
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('p-captcha.route_prefix', 'p-captcha'),
            'middleware' => ['web'],
            'namespace' => 'Core45\LaravelPCaptcha\Http\Controllers',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register package views
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'p-captcha');
    }

    /**
     * Register the @pcaptcha Blade directive
     */
    protected function registerBladeDirective(): void
    {
        Blade::directive('pcaptcha', function ($expression) {
            return "<?php echo app('p-captcha')->renderCaptcha({$expression}); ?>";
        });
    }

    /**
     * Register package commands
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Core45\LaravelPCaptcha\Console\InstallCommand::class,
            ]);
        }
    }

    /**
     * Register publishing for assets and config
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__.'/../../config/p-captcha.php' => config_path('p-captcha.php'),
            ], 'p-captcha-config');

            // Publish views
            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/p-captcha'),
            ], 'p-captcha-views');

            // Publish assets
            $this->publishes([
                __DIR__.'/../../resources/assets/css' => public_path('vendor/p-captcha/css'),
                __DIR__.'/../../resources/assets/js' => public_path('vendor/p-captcha/js'),
            ], 'p-captcha-assets');

            // Publish migrations (if needed)
            $this->publishes([
                __DIR__.'/../../database/migrations' => database_path('migrations'),
            ], 'p-captcha-migrations');
        }
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return ['p-captcha'];
    }
}
