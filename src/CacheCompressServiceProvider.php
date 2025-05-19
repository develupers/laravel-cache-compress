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

        // Register the compression macro - These will target CustomCacheRepository
        // Ensure macros correctly check for the CustomCacheRepository instance,
        // which is returned by CustomCacheManager::store()
        Cache::macro('compress', function (bool $enabled = true, ?int $level = null) {
            if ($this instanceof CustomCacheRepository) {
                $this->setCompressionSetting('enabled', $enabled);
                // Only set level if it's provided, otherwise setCompressionSetting will use its default logic
                if ($level !== null) {
                    $this->setCompressionSetting('level', $level);
                }
            } elseif ($this instanceof CacheManager || $this instanceof CustomCacheManager) {
                // If called on the manager, get the default store and apply settings to it.
                $defaultStore = $this->store();
                if ($defaultStore instanceof CustomCacheRepository) {
                    $defaultStore->setCompressionSetting('enabled', $enabled);
                    if ($level !== null) {
                        $defaultStore->setCompressionSetting('level', $level);
                    }
                }

                return $defaultStore; // Return the store for chaining
            }

            return $this; // Return $this for chaining if not handled above
        });

        Cache::macro('compressionLevel', function (int $level) {
            if ($this instanceof CustomCacheRepository) {
                $this->setCompressionSetting('level', $level);
                // setCompressionSetting initializes 'enabled' to its default if not already set.
            } elseif ($this instanceof CacheManager || $this instanceof CustomCacheManager) {
                $defaultStore = $this->store();
                if ($defaultStore instanceof CustomCacheRepository) {
                    $defaultStore->setCompressionSetting('level', $level);
                }

                return $defaultStore; // Return the store for chaining
            }

            return $this;
        });

        Cache::macro('getCompressionSettings', function () {
            if ($this instanceof CustomCacheRepository) {
                return $this->getCompressionSettingsForMacro();
            } elseif ($this instanceof CacheManager || $this instanceof CustomCacheManager) {
                $defaultStore = $this->store();
                if ($defaultStore instanceof CustomCacheRepository) {
                    return $defaultStore->getCompressionSettingsForMacro();
                }
            }

            return null;
        });

        Cache::macro('clearCompressionSettings', function () {
            if ($this instanceof CustomCacheRepository) {
                $this->clearCompressionSettingsForMacro();
            } elseif ($this instanceof CacheManager || $this instanceof CustomCacheManager) {
                $defaultStore = $this->store();
                if ($defaultStore instanceof CustomCacheRepository) {
                    $defaultStore->clearCompressionSettingsForMacro();
                }
            }

            return $this;
        });
    }
}
