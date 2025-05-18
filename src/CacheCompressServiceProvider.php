<?php

namespace Develupers\CacheCompress;

use Develupers\CacheCompress\Commands\CacheCompressCommand;
use Develupers\CacheCompress\Store\CustomCacheManager;
use Develupers\CacheCompress\Store\CustomCacheRepository;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CacheCompressServiceProvider extends PackageServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // dd('CacheCompressServiceProvider register() called'); // Commented out now
        parent::register(); // Call parent's register method first

        $this->app->singleton(CacheCompress::class);

        // 1. Register our manager under a unique key
        $this->app->singleton('cache.compress_manager', function ($app) {
            // dd('cache.compress_manager singleton is being resolved'); // Comment this out
            return new CustomCacheManager($app);
        });

        // 2. Alias the main 'cache' service to our custom manager.
        $this->app->alias('cache.compress_manager', 'cache');
        $this->app->alias('cache.compress_manager', \Illuminate\Cache\CacheManager::class);
        $this->app->alias('cache.compress_manager', \Illuminate\Contracts\Cache\Factory::class);

        $this->app->singleton('cache.store', function ($app) {
            // dd('\'cache.store\' singleton is being resolved, $app[cache] is: ' . get_class($app['cache']));
            return $app['cache']->driver(); 
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the compression macro - These will target CustomCacheRepository
        Cache::macro('compress', function (bool $enabled = true, ?int $level = null) {
            if ($this instanceof CustomCacheRepository) {
                $this->setCompressionSetting('enabled', $enabled);
                $this->setCompressionSetting('level', $level ?? config('cache-compress.compression_level', 6));
            }

            return $this;
        });

        Cache::macro('compressionLevel', function (int $level) {
            if ($this instanceof CustomCacheRepository) {
                $this->setCompressionSetting('level', $level);
                if (! isset($this->compressionSettings) || ! array_key_exists('enabled', $this->compressionSettings)) {
                    $this->setCompressionSetting('enabled', config('cache-compress.enabled', true));
                }
            }

            return $this;
        });

        // The getCompressionSettings and clearCompressionSettings macros might need adjustment
        // if CustomCacheRepository doesn't directly expose compressionSettings.
        // For now, assuming it will have a way to get/clear these or we'll adjust later.
        Cache::macro('getCompressionSettings', function () {
            if ($this instanceof CustomCacheRepository) {
                return $this->getCompressionSettingsForMacro();
            }

            return null;
        });

        Cache::macro('clearCompressionSettings', function () {
            if ($this instanceof CustomCacheRepository) {
                $this->clearCompressionSettingsForMacro();
            }

            return $this;
        });

        // Defer parent::boot() until the application is fully booted
        $this->app->booted(function () {
            if (method_exists(get_parent_class($this), 'boot')) {
                parent::boot();
            }
        });
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-cache-compress')
            ->hasConfigFile()
            ->hasCommand(CacheCompressCommand::class);
    }
}
