<?php

namespace Develupers\CacheCompress;

use Develupers\CacheCompress\Commands\CacheCompressCommand;
use Develupers\CacheCompress\Store\CustomCacheManager;
use Develupers\CacheCompress\Store\CustomCacheRepository;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CacheCompressServiceProvider extends PackageServiceProvider
{
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

    public function packageRegistered(): void
    {
        // This method is called after the package is registered (within the parent register method).
        // Ideal for service container bindings that should happen early.

        $this->app->singleton(CacheCompress::class);

        // Register our CustomCacheManager as the concrete implementation for cache services
        $this->app->singleton(CustomCacheManager::class, function (Application $app) {
            return new CustomCacheManager($app);
        });

        // Alias the main 'cache' service and related abstracts to our custom manager.
        $this->app->alias(CustomCacheManager::class, 'cache');
        $this->app->alias(CustomCacheManager::class, CacheManager::class);
        $this->app->alias(CustomCacheManager::class, Factory::class);

        // This ensures that when `cache.store` (or Repository::class) is resolved,
        // it uses our aliased 'cache' manager's default driver.
        $this->app->singleton('cache.store', function (Application $app) {
            return $app['cache']->driver();
        });
    }

    public function packageBooted(): void
    {
        // This method is called after the package is booted (within the parent boot method).
        // Ideal for registering macros, event listeners, publishing assets etc.

        // Register the compression macros on the CustomCacheRepository class
        CustomCacheRepository::macro('compress', function (bool $enabled = true, ?int $level = null) {
            $this->setCompressionSetting('enabled', $enabled);
            // Only set the level if it's provided, otherwise setCompressionSetting will use its default logic
            if ($level !== null) {
                $this->setCompressionSetting('level', $level);
            }

            return $this;
        });

        CustomCacheRepository::macro('compressionLevel', function (int $level) {
            $this->setCompressionSetting('level', $level);

            // setCompressionSetting initializes 'enabled' to its default if not already set.
            return $this;
        });

        CustomCacheRepository::macro('getCompressionSettings', function () {
            return $this->getCompressionSettingsForMacro();
        });

        CustomCacheRepository::macro('clearCompressionSettings', function () {
            $this->clearCompressionSettingsForMacro();

            return $this;
        });

        // Register the same macros on the CustomCacheManager to handle manager-level calls
        CustomCacheManager::macro('compress', function (bool $enabled = true, ?int $level = null) {
            $defaultStore = $this->driver();
            if ($defaultStore instanceof CustomCacheRepository) {
                $defaultStore->setCompressionSetting('enabled', $enabled);
                if ($level !== null) {
                    $defaultStore->setCompressionSetting('level', $level);
                }
            }

            return $defaultStore;
        });

        CustomCacheManager::macro('compressionLevel', function (int $level) {
            $defaultStore = $this->driver();
            if ($defaultStore instanceof CustomCacheRepository) {
                $defaultStore->setCompressionSetting('level', $level);
            }

            return $defaultStore;
        });

        CustomCacheManager::macro('getCompressionSettings', function () {
            $defaultStore = $this->driver();
            if ($defaultStore instanceof CustomCacheRepository) {
                return $defaultStore->getCompressionSettingsForMacro();
            }

            return null;
        });

        CustomCacheManager::macro('clearCompressionSettings', function () {
            $defaultStore = $this->driver();
            if ($defaultStore instanceof CustomCacheRepository) {
                $defaultStore->clearCompressionSettingsForMacro();
            }

            return $this;
        });
    }
}
